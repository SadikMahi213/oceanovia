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
        if (Commission::where('order_id', $order->id)->exists()) {
            return;
        }

        foreach ($order->items as $item) {
            $rate = $this->getSellerCommissionRate($item->seller_id);

            Commission::create([
                'order_id'      => $order->id,
                'order_item_id' => $item->id,
                'seller_id'     => $item->seller_id,
                'rate'          => $rate,
                'subtotal'      => $item->subtotal,
                'amount'        => round($item->subtotal * $rate / 100, 2),
                'status'        => 'pending',
            ]);
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

    public function markAsPaid(int $sellerId, int $payoutId): void
    {
        Commission::bySeller($sellerId)
            ->pending()
            ->update([
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
