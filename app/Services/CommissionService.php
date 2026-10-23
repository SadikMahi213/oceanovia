<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SellerProfile;

class CommissionService
{
    public function calculateForItem(OrderItem $item): float
    {
        $rate = $this->getSellerCommissionRate($item->seller_id);

        return round($item->subtotal * $rate / 100, 2);
    }

    public function createCommissions(Order $order): void
    {
        foreach ($order->items as $item) {
            // Stock-held items are not yet billable: they earn no commission
            // until the seller/supplier confirms the shortfall is sourced.
            if ($item->status === OrderItem::STATUS_AWAITING_STOCK) {
                continue;
            }

            $rate = $this->getSellerCommissionRate($item->seller_id);

            Commission::firstOrCreate(
                ['order_item_id' => $item->id],
                [
                    'order_id'  => $order->id,
                    'seller_id' => $item->seller_id,
                    'rate'      => $rate,
                    'subtotal'  => $item->subtotal,
                    'amount'    => round($item->subtotal * $rate / 100, 2),
                    'status'    => 'pending',
                ]
            );
        }
    }

    public function getCommissionForItem(OrderItem $item): Commission
    {
        return Commission::firstOrCreate(
            ['order_item_id' => $item->id],
            [
                'order_id'  => $item->order_id,
                'seller_id' => $item->seller_id,
                'rate'      => $this->getSellerCommissionRate($item->seller_id),
                'subtotal'  => $item->subtotal,
                'amount'    => round($item->subtotal * $this->getSellerCommissionRate($item->seller_id) / 100, 2),
                'status'    => 'pending',
            ]
        );
    }

    public function netForItem(OrderItem $item): float
    {
        return round((float) $item->subtotal - $this->getCommissionForItem($item)->amount, 2);
    }

    public function getPendingTotal(int $sellerId): float
    {
        return (float) Commission::bySeller($sellerId)->pending()->sum('amount');
    }

    public function getPaidTotal(int $sellerId): float
    {
        return (float) Commission::bySeller($sellerId)->paid()->sum('amount');
    }

    /**
     * Mark the commissions a payout covers as paid.
     *
     * A payout pays a fixed amount, so it must only settle the pending
     * commissions that fit into it, oldest first. Marking every pending
     * commission as paid would record commissions as paid that the payout
     * never covered.
     */
    public function markAsPaid(int $sellerId, int $payoutId, ?float $amount = null): void
    {
        $query = Commission::bySeller($sellerId)
            ->pending()
            ->orderBy('created_at')
            ->orderBy('id');

        if ($amount === null) {
            $query->update([
                'status'    => 'paid',
                'paid_at'   => now(),
                'payout_id' => $payoutId,
            ]);

            return;
        }

        $remaining = round($amount, 2);

        if ($remaining <= 0) {
            return;
        }

        $ids = [];

        foreach ($query->get() as $commission) {
            if ($remaining <= 0) {
                break;
            }

            $ids[] = $commission->id;
            $remaining = round($remaining - (float) $commission->amount, 2);
        }

        if ($ids === []) {
            return;
        }

        Commission::whereIn('id', $ids)->update([
            'status'    => 'paid',
            'paid_at'   => now(),
            'payout_id' => $payoutId,
        ]);
    }

    private function getSellerCommissionRate(int $sellerId): float
    {
        $profile = SellerProfile::where('user_id', $sellerId)->first();

        return $profile?->commission_rate ? (float) $profile->commission_rate : 10.0;
    }
}
