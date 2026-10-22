<?php

namespace App\Services;

use App\Models\CodCollection;
use App\Models\Commission;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    /**
     * Bounded retry attempts for the cancellation transaction.
     *
     * The same budget the cart mutations and the checkout use. Laravel only
     * retries a transaction when the failure is a recognised concurrency error
     * (an InnoDB deadlock or a lock wait timeout); every other failure is
     * re-thrown immediately. Each attempt runs the whole closure again, which
     * re-reads the order and takes the stock locks again, so a retry cannot
     * reuse stale state.
     */
    public const TRANSACTION_ATTEMPTS = TransactionRetry::ATTEMPTS;

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

            // Work out, before anything is mutated, whether this operation
            // would leave the order with no active items - that is, whether
            // it would cancel the whole order. This has to be decided up
            // front so a refusal cannot leave stock, commissions, COD
            // collections or item states partially updated.
            $remainingAfterCancel = $locked->items()
                ->whereNotIn('id', $itemIdsToCancel)
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->count();

            // A paid order must be refunded (see RefundService), not
            // cancelled: cancelling it would mark the order as cancelled while
            // the payment stays recorded as paid, the stock is returned and
            // the seller credit is never reversed. Rejecting a subset of items
            // that leaves other items active does not change the order's own
            // status and stays allowed.
            if ($remainingAfterCancel === 0 && $locked->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'cancel' => 'This order has already been paid and cannot be cancelled. Please request a refund instead.',
                ]);
            }

            if (CodCollection::whereIn('order_item_id', $itemIdsToCancel)
                ->where('status', 'collected')
                ->exists()) {
                throw ValidationException::withMessages([
                    'cancel' => 'Cash has already been collected for this order. Process a refund instead of cancelling.',
                ]);
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

            // The local inventory is restored after the procurement handling,
            // so the supplier source stock is locked before the local
            // inventory. That is the same order the checkout takes
            // (SourceStockService::reserveForCart, then
            // InventoryService::reserveAndDecrementForCart), so a checkout and
            // a cancellation of the same products can no longer deadlock by
            // taking the two rows in opposite order. The restoration reads
            // nothing that the preceding writes changed, so the result of the
            // cancellation is the same.
            foreach ($items as $item) {
                if (in_array($item->status, ['cancelled', 'returned', 'shipped', 'delivered'], true)) {
                    continue;
                }

                $this->restoreLocalInventory($item);
            }

            app(AuditService::class)->logFinancial('order_cancelled', $locked, [
                'order_number' => $locked->order_number,
                'reason'       => $reason,
                'item_ids'     => $itemIdsToCancel,
                'cancelled_by' => $actorId,
            ]);

            return true;
        }, self::TRANSACTION_ATTEMPTS);
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
