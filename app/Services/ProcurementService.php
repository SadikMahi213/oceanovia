<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProcurementOrder;
use App\Models\ProcurementOrderItem;
use App\Models\SupplierBalance;
use App\Models\SupplierProductStock;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and drives procurement orders. Procurement order status is
 * derived from the customer order_item statuses, so the existing
 * seller/supplier status flows remain the single source of truth.
 */
class ProcurementService
{
    /**
     * Create one procurement order per (order, supplier, seller) for all
     * sourced order items. Idempotent via unique constraints.
     */
    public function createForOrder(Order $order): int
    {
        $items = OrderItem::where('order_id', $order->id)
            ->whereNotNull('supplier_product_id')
            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $created = 0;

        foreach ($items->groupBy(fn ($item) => $item->supplier_id.'-'.$item->seller_id) as $group) {
            $first = $group->first();

            $po = ProcurementOrder::firstOrCreate(
                [
                    'order_id'     => $order->id,
                    'supplier_id'  => $first->supplier_id,
                    'seller_id'    => $first->seller_id,
                ],
                ['status' => 'pending']
            );

            if ($po->wasRecentlyCreated) {
                $created++;
            }

            foreach ($group as $item) {
                ProcurementOrderItem::updateOrCreate(
                    ['order_item_id' => $item->id],
                    [
                        'procurement_order_id' => $po->id,
                        'product_id'           => $item->product_id,
                        'supplier_product_id'  => $item->supplier_product_id,
                        'product_name'         => $item->product_name,
                        'sku'                  => $item->sku,
                        'quantity'             => $item->quantity,
                        'unit_cost'            => (float) ($item->unit_cost ?? 0),
                        'subtotal'             => round((float) ($item->unit_cost ?? 0) * $item->quantity, 2),
                        'status'               => $item->status ?? 'pending',
                    ]
                );
            }

            $po->subtotal = round((float) $po->items()->sum('subtotal'), 2);
            $po->total_quantity = (int) $po->items()->sum('quantity');
            $po->save();

            if ($po->wasRecentlyCreated) {
                $this->notifySupplier($po, 'procurement_order_created',
                    'New procurement order '.$po->po_number,
                    route('supplier.procurement.show', $po));
            }
        }

        return $created;
    }

    /**
     * Re-derive procurement statuses for every PO of an order from the
     * underlying order_item statuses. Safe to call after any status change.
     */
    public function syncForOrder(Order $order): void
    {
        $procurements = ProcurementOrder::where('order_id', $order->id)
            ->with('items.orderItem')
            ->get();

        foreach ($procurements as $po) {
            $this->syncProcurement($po);
        }
    }

    public function syncProcurement(ProcurementOrder $po): void
    {
        foreach ($po->items as $poItem) {
            $orderItemStatus = $poItem->orderItem?->status ?? 'pending';

            if ($poItem->status !== $orderItemStatus) {
                $poItem->status = $orderItemStatus;
                $poItem->save();
            }
        }

        $target = $this->aggregateStatus($po->items->map(fn ($pi) => $pi->status)->all());

        if ($target === $po->status) {
            return;
        }

        $previous = $po->status;
        $po->status = $target;

        if ($target === 'processing') {
            $po->accepted_at = $po->accepted_at ?? now();
        }
        if ($target === 'packed') {
            $po->packed_at = $po->packed_at ?? now();
        }
        if ($target === 'ready_for_pickup') {
            $po->ready_at = $po->ready_at ?? now();
        }
        if ($target === 'shipped') {
            $po->shipped_at = $po->shipped_at ?? now();
        }
        if ($target === 'delivered') {
            $po->delivered_at = $po->delivered_at ?? now();
        }
        if (in_array($target, ['cancelled', 'returned'], true)) {
            $po->cancelled_at = $po->cancelled_at ?? now();
        }

        $po->save();

        // Commit reserved stock when first reaching a shipped/delivered state.
        if (in_array($target, ['shipped', 'delivered'], true)
            && ! in_array($previous, ['shipped', 'delivered'], true)) {
            app(SourceStockService::class)->commitForProcurement($po);
        }

        // Settle the supplier once, on delivery.
        if ($target === 'delivered' && $previous !== 'delivered') {
            $this->settle($po);
        }

        // On cancellation/return: reverse settlement if already paid out,
        // and release any reservations that were never committed.
        if (in_array($target, ['cancelled', 'returned'], true)) {
            if ($po->settled_at !== null) {
                $this->reverseSettlement($po);
            }

            if (! in_array($previous, ['shipped', 'delivered'], true)) {
                app(SourceStockService::class)->releaseForProcurement($po);
            }
        }
    }

    /**
     * Cancel every open procurement for an order and release reservations.
     * Used on order cancellation paths where item statuses are not driven
     * by the seller/supplier flows.
     */
    public function cancelForOrder(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            app(SourceStockService::class)->releaseForOrder($order->id);

            $procurements = ProcurementOrder::where('order_id', $order->id)
                ->whereNotIn('status', ['shipped', 'delivered'])
                ->get();

            foreach ($procurements as $po) {
                $po->items()->update(['status' => 'cancelled']);
                $po->update([
                    'status'             => 'cancelled',
                    'cancelled_at'       => now(),
                    'cancellation_reason' => $reason,
                ]);
            }
        });
    }

    public function updateTracking(ProcurementOrder $po, ?string $carrier, ?string $trackingNumber, ?string $trackingUrl = null): void
    {
        $po->update([
            'carrier'         => $carrier ?: null,
            'tracking_number' => $trackingNumber ?: null,
            'tracking_url'    => $trackingUrl ?: null,
        ]);

        if ($po->status === 'shipped' && $trackingNumber) {
            $this->notifySeller($po, 'procurement_shipped',
                'Procurement order '.$po->po_number.' shipped',
                route('seller.procurement.show', $po));
        }
    }

    private function settle(ProcurementOrder $po): void
    {
        if ($po->settled_at !== null || $po->supplier_id === null) {
            return;
        }

        $balance = SupplierBalance::firstOrCreate(
            ['supplier_id' => $po->supplier_id],
            ['balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'platform_fees' => 0]
        );

        $amount = (float) $po->subtotal;

        $balance->increment('balance', $amount);
        $balance->increment('total_earned', $amount);

        Transaction::create([
            'accountable_type' => SupplierBalance::class,
            'accountable_id'   => $balance->id,
            'reference_type'   => ProcurementOrder::class,
            'reference_id'     => $po->id,
            'type'             => 'credit',
            'amount'           => $amount,
            'description'      => 'Procurement settlement: '.$po->po_number,
            'status'           => 'completed',
            'method'           => 'procurement',
        ]);

        $po->update(['settled_at' => now()]);

        $this->notifySupplier($po, 'procurement_settlement',
            'Settlement for '.$po->po_number,
            route('supplier.wallet.index'));
    }

    private function reverseSettlement(ProcurementOrder $po): void
    {
        if ($po->settled_at === null) {
            return;
        }

        $balance = SupplierBalance::where('supplier_id', $po->supplier_id)->first();

        if ($balance) {
            $amount = (float) $po->subtotal;

            $balance->decrement('balance', $amount);
            $balance->decrement('total_earned', $amount);

            Transaction::create([
                'accountable_type' => SupplierBalance::class,
                'accountable_id'   => $balance->id,
                'reference_type'   => ProcurementOrder::class,
                'reference_id'     => $po->id,
                'type'             => 'refund',
                'amount'           => $amount,
                'description'      => 'Procurement reversal: '.$po->po_number,
                'status'           => 'completed',
                'method'           => 'procurement',
            ]);
        }

        $po->update(['settled_at' => null]);
    }

    private function aggregateStatus(array $statuses): string
    {
        $rank = [
            'cancelled'        => 0,
            'returned'         => 1,
            'pending'          => 2,
            'processing'       => 3,
            'packed'           => 4,
            'ready_for_pickup' => 5,
            'shipped'          => 6,
            'delivered'        => 7,
        ];

        $unique = array_values(array_unique($statuses));
        $unique = $unique === [] ? ['pending'] : $unique;

        if (count($unique) === 1) {
            $single = $unique[0];

            if ($single === 'refunded') {
                return 'returned';
            }

            return array_key_exists($single, $rank) ? $single : 'pending';
        }

        // Mixed statuses: treat as the furthest progress reached.
        $ranks = array_map(fn ($s) => $rank[$s] ?? 2, $unique);

        return array_search(max($ranks), $rank, true);
    }

    private function notifySupplier(ProcurementOrder $po, string $type, string $title, string $link): void
    {
        $this->notifyUser($po->supplier_id, $type, $title, 'shopping-cart', $link, ['po_number' => $po->po_number]);
    }

    private function notifySeller(ProcurementOrder $po, string $type, string $title, string $link): void
    {
        $this->notifyUser($po->seller_id, $type, $title, 'truck', $link, ['po_number' => $po->po_number]);
    }

    private function notifyUser(int $userId, string $type, string $title, string $icon, string $link, array $data = []): void
    {
        UserNotification::create([
            'id'               => (string) Str::uuid(),
            'type'             => $type,
            'notifiable_type'  => User::class,
            'notifiable_id'    => $userId,
            'data'             => $data,
            'title'            => $title,
            'icon'             => $icon,
            'link'             => $link,
        ]);
    }
}