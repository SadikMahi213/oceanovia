<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\SellerBalance;
use App\Models\SellerPayout;
use App\Models\Transaction;

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

    public function credit(int $sellerId, float $amount): void
    {
        $balance = $this->getBalance($sellerId);

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
    }

    public function reverse(int $sellerId, float $amount): void
    {
        $balance = $this->getBalance($sellerId);

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
    }

    public function requestPayout(int $sellerId, float $amount, string $paymentMethod, array $accountDetails = []): SellerPayout
    {
        $balance = $this->getBalance($sellerId);

        if ($amount > $balance->balance) {
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

        return $payout;
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
        $this->getBalance($payout->seller_id)->increment('balance', $payout->amount);
        $this->getBalance($payout->seller_id)->decrement('total_withdrawn', $payout->amount);

        $payout->update([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
