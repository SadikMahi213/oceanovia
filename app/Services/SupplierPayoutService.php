<?php

namespace App\Services;

use App\Exceptions\PayoutStateException;
use App\Models\SupplierBalance;
use App\Models\SupplierPayout;
use App\Models\Transaction;
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPayoutService
{
    public const PLATFORM_FEE_RATE = 0.02;

    public const TAX_RATE = 0.01;

    public function request(int $supplierId, array $data): SupplierPayout
    {
        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The payout amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($supplierId, $data, $amount) {
            $balance = $this->balanceForUpdate($supplierId);

            if ($amount > (float) $balance->balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Requested amount exceeds your available balance.',
                ]);
            }

            $platformFee = round($amount * self::PLATFORM_FEE_RATE, 2);
            $tax = round($amount * self::TAX_RATE, 2);
            $netAmount = round($amount - $platformFee - $tax, 2);

            $payout = SupplierPayout::create([
                'supplier_id' => $supplierId,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'tax' => $tax,
                'net_amount' => $netAmount,
                'payment_method' => $data['payment_method'],
                'account_details' => $data['account_details'],
                'status' => SupplierPayout::STATUS_PENDING,
            ]);

            $balance->decrement('balance', $amount);
            $balance->increment('pending_balance', $amount);

            Transaction::create([
                'accountable_type' => SupplierBalance::class,
                'accountable_id' => $balance->id,
                'reference_type' => SupplierPayout::class,
                'reference_id' => $payout->id,
                'type' => 'payout',
                'amount' => $amount,
                'description' => 'Payout requested',
                'status' => 'pending',
                'method' => $data['payment_method'],
            ]);

            return $payout->fresh();
        }, TransactionRetry::ATTEMPTS);
    }

    public function approve(SupplierPayout $payout, int $adminId, ?string $notes = null): SupplierPayout
    {
        return DB::transaction(function () use ($payout, $adminId, $notes) {
            $payout = SupplierPayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->status !== SupplierPayout::STATUS_PENDING) {
                throw new PayoutStateException('Only pending supplier payouts can be approved.');
            }

            $payout->update([
                'status' => SupplierPayout::STATUS_PROCESSING,
                'admin_notes' => $notes,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ]);

            return $payout->fresh();
        }, TransactionRetry::ATTEMPTS);
    }

    public function complete(SupplierPayout $payout, int $adminId, ?string $notes = null): SupplierPayout
    {
        return DB::transaction(function () use ($payout, $adminId, $notes) {
            $payout = SupplierPayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->status === SupplierPayout::STATUS_COMPLETED) {
                return $payout;
            }

            if ($payout->status !== SupplierPayout::STATUS_PROCESSING) {
                throw new PayoutStateException('Only processing supplier payouts can be completed.');
            }

            $balance = $this->balanceForUpdate($payout->supplier_id);
            $amount = (float) $payout->amount;

            if ($amount > (float) $balance->pending_balance) {
                throw new PayoutStateException('Supplier pending balance is lower than the payout amount.');
            }

            $balance->decrement('pending_balance', $amount);
            $balance->increment('total_withdrawn', $amount);
            $balance->increment('platform_fees', (float) $payout->platform_fee);

            $payout->update([
                'status' => SupplierPayout::STATUS_COMPLETED,
                'admin_notes' => $notes ?? $payout->admin_notes,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ]);

            $this->markLedger($payout, $balance, 'completed', 'Payout completed');

            return $payout->fresh();
        }, TransactionRetry::ATTEMPTS);
    }

    public function reject(SupplierPayout $payout, int $adminId, string $reason, ?string $notes = null): SupplierPayout
    {
        return DB::transaction(function () use ($payout, $adminId, $reason, $notes) {
            $payout = SupplierPayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($payout->status === SupplierPayout::STATUS_REJECTED) {
                return $payout;
            }

            if (! in_array($payout->status, [SupplierPayout::STATUS_PENDING, SupplierPayout::STATUS_PROCESSING], true)) {
                throw new PayoutStateException('Only pending or processing supplier payouts can be rejected.');
            }

            $balance = $this->balanceForUpdate($payout->supplier_id);
            $amount = (float) $payout->amount;

            if ($amount > (float) $balance->pending_balance) {
                throw new PayoutStateException('Supplier pending balance is lower than the payout amount.');
            }

            $balance->decrement('pending_balance', $amount);
            $balance->increment('balance', $amount);

            $payout->update([
                'status' => SupplierPayout::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'admin_notes' => $notes ?? $payout->admin_notes,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ]);

            $this->markLedger($payout, $balance, 'failed', 'Payout rejected: '.$reason);

            return $payout->fresh();
        }, TransactionRetry::ATTEMPTS);
    }

    private function balanceForUpdate(int $supplierId): SupplierBalance
    {
        SupplierBalance::firstOrCreate(['supplier_id' => $supplierId]);

        $balance = SupplierBalance::where('supplier_id', $supplierId)->lockForUpdate()->first();

        if (! $balance) {
            throw new PayoutStateException('Supplier balance not found.');
        }

        return $balance;
    }

    private function markLedger(SupplierPayout $payout, SupplierBalance $balance, string $status, string $description): void
    {
        Transaction::where('accountable_type', SupplierBalance::class)
            ->where('accountable_id', $balance->id)
            ->where('reference_type', SupplierPayout::class)
            ->where('reference_id', $payout->id)
            ->where('type', 'payout')
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'description' => $description,
            ]);
    }
}
