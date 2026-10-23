<?php

namespace App\Services;

use App\Exceptions\PayoutStateException;
use App\Models\SellerBalance;
use App\Models\SellerPayout;
use App\Models\Transaction;
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * The states a payout goes through.
     *
     * A payout reserves its amount when it is requested, so the money leaves
     * the balance at that moment and the payout only records what happens to
     * it afterwards:
     *
     *   pending --approve--> approved --complete--> completed
     *      |
     *      +--reject--> rejected (the reserved amount goes back to the balance)
     *
     * The service is the boundary that keeps a payout on that path, whatever
     * calls it.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

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

    /**
     * Credit a seller.
     *
     * This runs inside the transaction of whoever calls it (the COD
     * collection recording and the refund workflow), so it must not ask for
     * retries of its own: Laravel cannot retry a nested transaction and
     * would raise a DeadlockException instead. The enclosing transaction
     * owns the retry behaviour.
     */
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

    /**
     * Reverse a seller credit.
     *
     * Like credit(), this runs inside the transaction of the calling
     * workflow, so the enclosing transaction owns any retry.
     */
    public function reverse(int $sellerId, float $amount, array $reference = []): void
    {
        DB::transaction(function () use ($sellerId, $amount, $reference) {
            $balance = $this->lockBalance($sellerId);

            $balance->decrement('balance', $amount);
            $balance->decrement('total_earned', $amount);

            Transaction::create([
                'accountable_type' => SellerBalance::class,
                'accountable_id'   => $balance->id,
                'reference_type'   => $reference['reference_type'] ?? null,
                'reference_id'     => $reference['reference_id'] ?? null,
                'type'             => 'debit',
                'amount'           => $amount,
                'description'      => $reference['description'] ?? 'Reversal (refund)',
                'status'           => 'completed',
                'method'           => 'refund',
            ]);

            app(AuditService::class)->logFinancial('seller_debit', $balance, [
                'seller_id'      => $sellerId,
                'amount'         => $amount,
                'order_id'       => $reference['order_id'] ?? null,
                'order_item_id'  => $reference['order_item_id'] ?? null,
            ]);
        });
    }

    public function requestPayout(int $sellerId, float $amount, string $paymentMethod, array $accountDetails = []): SellerPayout
    {
        // A payout of nothing, or of a negative amount, would take the money
        // into the other direction. The check belongs here, so every caller is
        // covered and not only the ones that validate a form.
        if ($amount <= 0) {
            throw new \InvalidArgumentException('The amount must be greater than zero.');
        }

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
        }, TransactionRetry::ATTEMPTS);
    }

    /**
     * Approve a payout that is waiting.
     *
     * The payout is read and locked inside the transaction, so two approvals
     * of the same payout cannot both go through: the second one finds the
     * payout approved already and is refused without changing anything.
     */
    public function approve(SellerPayout $payout): void
    {
        DB::transaction(function () use ($payout) {
            $locked = SellerPayout::whereKey($payout->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== self::STATUS_PENDING) {
                throw new PayoutStateException(
                    'A payout with the status "'.$locked->status.'" cannot be approved.'
                );
            }

            $locked->update([
                'status'       => self::STATUS_APPROVED,
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
            ]);
        });
    }

    /**
     * Complete an approved payout.
     *
     * The amount was reserved when the payout was requested, so completing it
     * does not touch the balance a second time. It marks the commissions that
     * the payout pays as paid, and the payout itself as paid.
     *
     * The payout status and the commissions it pays are one financial
     * operation: if the commissions cannot be marked as paid, the payout must
     * not stay completed either, otherwise the seller is recorded as paid
     * while the money never reached their commissions.
     */
    public function complete(SellerPayout $payout): void
    {
        DB::transaction(function () use ($payout) {
            $locked = SellerPayout::whereKey($payout->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return;
            }

            if ($locked->status === self::STATUS_COMPLETED) {
                // The payout was completed already. Completing it again would
                // move the recorded moment and repeat the effect on the
                // commissions, so it does nothing.
                return;
            }

            if ($locked->status !== self::STATUS_APPROVED) {
                throw new PayoutStateException(
                    'A payout with the status "'.$locked->status.'" cannot be completed.'
                );
            }

            $now = now();

            $locked->update([
                'status'       => self::STATUS_COMPLETED,
                'completed_at' => $now,
                'paid_at'      => $now,
            ]);

            app(CommissionService::class)->markAsPaid($locked->seller_id, $locked->id, (float) $locked->amount);
        }, TransactionRetry::ATTEMPTS);
    }

    /**
     * Reject a payout that is waiting and give the reserved amount back.
     *
     * Only a pending payout can be rejected. A payout that was completed or
     * rejected already must not restore the funds a second time, so the
     * balance is only touched for a payout that is still waiting.
     */
    public function reject(SellerPayout $payout, string $reason = ''): void
    {
        DB::transaction(function () use ($payout, $reason) {
            // The payout is re-read and locked inside the transaction. A retry
            // must not reuse the model of a rolled back attempt: it would look
            // unchanged, Eloquent would skip the update, and the balance would
            // be refunded while the payout stayed pending.
            $locked = SellerPayout::whereKey($payout->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return;
            }

            if ($locked->status !== self::STATUS_PENDING) {
                throw new PayoutStateException(
                    'A payout with the status "'.$locked->status.'" cannot be rejected.'
                );
            }

            $balance = $this->lockBalance($locked->seller_id);

            $balance->increment('balance', $locked->amount);
            $balance->decrement('total_withdrawn', $locked->amount);

            $locked->update([
                'status'           => self::STATUS_REJECTED,
                'rejected_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            app(AuditService::class)->logFinancial('payout_rejected', $locked, [
                'seller_id' => $locked->seller_id,
                'amount'    => $locked->amount,
                'reason'    => $reason,
            ]);
        }, TransactionRetry::ATTEMPTS);
    }
}
