<?php

namespace App\Services;

use App\Models\CodCollection;
use App\Models\Commission;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    /**
     * Cancel an order (or a subset of its items) while restoring eligible
     * stock, releasing source reservations and reversing unpaid commissions.
     * Safe to call repeatedly: already-cancelled items are skipped.
     *
     * @param  array<int, int>|null  $itemIds  null = whole order
     */
    public function cancel(Order $order, ?string $reason = null, ?array $itemIds = null, ?int $actorId = null): bool
    {
        return DB::transaction(function () use ($order, $reason, $itemIds, $actorId) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return false;
            }

            $items = $itemIds === null
                ? $locked->items()->with('product.inventory')->get()
                : $locked->items()->whereIn('id', $itemIds)->with('product.inventory')->get();

            if ($items->isEmpty()) {
                return false;
            }

            $itemIdsToCancel = $items->pluck('id')->all();

            if (CodCollection::whereIn('order_item_id', $itemIdsToCancel)
                ->where('status', 'collected')
                ->exists()) {
                throw ValidationException::withMessages([
                    'cancel' => 'Cash has already been collected for this order. Process a refund instead of cancelling.',
                ]);
            }

            foreach ($items as $item) {
                if (in_array($item->status, ['cancelled', 'returned', 'shipped', 'delivered'], true)) {
                    continue;
                }

                $this->restoreLocalInventory($item);
            }

            OrderItem::whereIn('id', $itemIdsToCancel)
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->update(['status' => 'cancelled']);

            Commission::whereIn('order_item_id', $itemIdsToCancel)
                ->where('status', 'pending')
                ->update(['status' => 'reversed']);

            CodCollection::whereIn('order_item_id', $itemIdsToCancel)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $remaining = $locked->items()
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->count();

            if ($remaining === 0) {
                $locked->update([
                    'status'               => 'cancelled',
                    'cancelled_at'         => $locked->cancelled_at ?? now(),
                    'cancellation_reason'  => $reason ?? $locked->cancellation_reason,
                ]);

                app(ProcurementService::class)->cancelForOrder($locked, $reason);
            } else {
                app(ProcurementService::class)->syncForOrder($locked);
            }

            app(AuditService::class)->logFinancial('order_cancelled', $locked, [
                'order_number' => $locked->order_number,
                'reason'       => $reason,
                'item_ids'     => $itemIdsToCancel,
                'cancelled_by' => $actorId,
            ]);

            return true;
        });
    }

    private function restoreLocalInventory(OrderItem $item): void
    {
        if ($item->supplier_product_id) {
            return;
        }

        $inventory = $item->product?->inventory;

        if ($inventory === null) {
            return;
        }

        Inventory::whereKey($inventory->getKey())
            ->lockForUpdate()
            ->increment('stock_quantity', $item->quantity);
    }
}
