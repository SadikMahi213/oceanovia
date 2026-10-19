<?php

namespace App\Services;

use App\Models\SellerBalance;
use App\Models\SellerPayout;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function getBalance(int $sellerId): SellerBalance
    {
        return SellerBalance::firstOrCreate(
            ['seller_id' => $sellerId],
            [
                'balance'         => 0,
                'total_earned'    => 0,
                'total_withdrawn' => 0,
            ]
        );
    }

    private function lockBalance(int $sellerId): SellerBalance
    {
        $balance = $this->getBalance($sellerId);

        return SellerBalance::whereKey($balance->getKey())->lockForUpdate()->first();
    }

    public function credit(int $sellerId, float $amount): void
    {
        DB::transaction(function () use ($sellerId, $amount) {
            $balance = $this->lockBalance($sellerId);

            $balance->increment('balance', $amount);
            $balance->increment('total_earned', $amount);

            Transaction::create([
                'accountable_type' => SellerBalance::class,
                'accountable_id'   => $balance->id,
                'type'             => 'credit',
                'amount'           => $amount,
                'description'      => 'Sale credit (net of commission)',
                'status'           => 'completed',
                'method'           => 'sale',
            ]);

            app(AuditService::class)->logFinancial('seller_credit', $balance, [
                'seller_id' => $sellerId,
                'amount'    => $amount,
            ]);
        });
    }

    public function reverse(int $sellerId, float $amount): void
    {
        DB::transaction(function () use ($sellerId, $amount) {
            $balance = $this->lockBalance($sellerId);

            $balance->decrement('balance', $amount);
            $balance->decrement('total_earned', $amount);

            Transaction::create([
                'accountable_type' => SellerBalance::class,
                'accountable_id'   => $balance->id,
                'type'             => 'debit',
                'amount'           => $amount,
                'description'      => 'Reversal (refund)',
                'status'           => 'completed',
                'method'           => 'refund',
            ]);

            app(AuditService::class)->logFinancial('seller_debit', $balance, [
                'seller_id' => $sellerId,
                'amount'    => $amount,
            ]);
        });
    }

    public function requestPayout(int $sellerId, float $amount, string $paymentMethod, array $accountDetails = []): SellerPayout
    {
        return DB::transaction(function () use ($sellerId, $amount, $paymentMethod, $accountDetails) {
            $balance = $this->lockBalance($sellerId);

            if ($amount > (float) $balance->balance) {
                throw new \InvalidArgumentException('Insufficient balance.');
            }

            $balance->decrement('balance', $amount);
            $balance->increment('total_withdrawn', $amount);

            $payout = SellerPayout::create([
                'seller_id'       => $sellerId,
                'amount'          => $amount,
                'fee'             => 0,
                'net_amount'      => $amount,
                'status'          => 'pending',
                'payment_method'  => $paymentMethod,
                'account_details' => $accountDetails,
            ]);

            Transaction::create([
                'accountable_type' => SellerBalance::class,
                'accountable_id'   => $balance->id,
                'reference_type'   => SellerPayout::class,
                'reference_id'     => $payout->id,
                'type'             => 'payout',
                'amount'           => $amount,
                'description'      => 'Payout requested',
                'status'           => 'pending',
                'method'           => $paymentMethod,
            ]);

            app(AuditService::class)->logFinancial('payout_requested', $payout, [
                'seller_id' => $sellerId,
                'amount'    => $amount,
            ]);

            return $payout;
        });
    }

    public function approve(SellerPayout $payout): void
    {
        $payout->update([
            'status'       => 'approved',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);
    }

    public function complete(SellerPayout $payout): void
    {
        $payout->update([
            'status'        => 'completed',
            'completed_at'  => now(),
        ]);

        app(CommissionService::class)->markAsPaid($payout->seller_id, $payout->id);
    }

    public function reject(SellerPayout $payout, string $reason = ''): void
    {
        DB::transaction(function () use ($payout, $reason) {
            $balance = $this->lockBalance($payout->seller_id);

            $balance->increment('balance', $payout->amount);
            $balance->decrement('total_withdrawn', $payout->amount);

            $payout->update([
                'status'           => 'rejected',
                'rejected_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            app(AuditService::class)->logFinancial('payout_rejected', $payout, [
                'seller_id' => $payout->seller_id,
                'amount'    => $payout->amount,
                'reason'    => $reason,
            ]);
        });
    }
}
