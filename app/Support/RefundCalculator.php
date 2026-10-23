<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundReversal;
use App\Services\CommissionService;
use App\Services\RefundService;

/**
 * Splits a refund amount into per-item seller reversals.
 *
 * A refund can be made for a single item or for the whole order, and it can
 * cover the full item value or only part of it. The split is proportional:
 * an item is only reversed for the share of its value that the refund
 * actually covers, and the already reversed share of every item is taken
 * into account so that repeated partial refunds can never reverse more than
 * was credited.
 *
 * The allocation is done in integer cents, so the parts always add up to the
 * amount that was refunded without rounding drift.
 */
class RefundCalculator
{
    /**
     * @return array<int, array{item: OrderItem, refund_amount: float, net_amount: float}>
     */
    public function allocate(Order $order, Refund $refund): array
    {
        $amountCents = (int) round((float) $refund->amount * 100);

        if ($amountCents <= 0) {
            return [];
        }

        $items = $refund->order_item_id !== null
            ? $order->items->where('id', $refund->order_item_id)
            : $order->items;

        $candidates = [];

        foreach ($items as $item) {
            $remainingCents = $this->remainingRefundableCents($order, $item, $refund);

            if ($remainingCents <= 0) {
                continue;
            }

            $candidates[] = [
                'item' => $item,
                'remaining' => $remainingCents,
            ];
        }

        $totalRemaining = array_sum(array_column($candidates, 'remaining'));

        if ($totalRemaining <= 0) {
            return [];
        }

        $toAllocate = min($amountCents, $totalRemaining);
        $netByItem = $this->netByItem($candidates);

        $allocations = [];
        $allocated = 0;

        foreach ($candidates as $index => $candidate) {
            $isLast = $index === count($candidates) - 1;

            if ($isLast) {
                $refundCents = $toAllocate - $allocated;
            } else {
                $refundCents = (int) floor($toAllocate * $candidate['remaining'] / $totalRemaining);
            }

            $refundCents = max(0, min($refundCents, $candidate['remaining']));
            $allocated += $refundCents;

            $netCents = (int) round($refundCents * $netByItem[$index]);

            $allocations[] = [
                'item' => $candidate['item'],
                'refund_amount' => $refundCents / 100,
                'net_amount' => $netCents / 100,
            ];
        }

        return $allocations;
    }

    /**
     * The value of an item that has not been refunded yet, in cents.
     */
    private function remainingRefundableCents(Order $order, OrderItem $item, Refund $current): int
    {
        $subtotalCents = (int) round((float) $item->subtotal * 100);

        $refundedCents = (int) round(
            (float) Refund::where('order_id', $order->id)
                ->where('order_item_id', $item->id)
                ->whereKeyNot($current->getKey())
                ->whereIn('status', ['approved', RefundService::STATUS_STRIPE_REFUNDED, 'completed'])
                ->sum('amount') * 100
        );

        $reversedCents = (int) round(
            (float) RefundReversal::where('order_item_id', $item->id)->sum('refund_amount') * 100
        );

        return max(0, $subtotalCents - max($refundedCents, $reversedCents));
    }

    /**
     * @param  array<int, array{item: OrderItem, remaining: int}>  $candidates
     * @return array<int, float> net amount per refunded cent
     */
    private function netByItem(array $candidates): array
    {
        $netByItem = [];
        $commissionService = app(CommissionService::class);

        foreach ($candidates as $index => $candidate) {
            $item = $candidate['item'];
            $net = $item->seller_id ? $commissionService->netForItem($item) : 0.0;
            $subtotal = (float) $item->subtotal;

            $netByItem[$index] = $subtotal > 0 ? min(1.0, max(0.0, $net / $subtotal)) : 0.0;
        }

        return $netByItem;
    }
}
