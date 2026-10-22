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
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Creates and drives procurement orders. Procurement order status is
 * derived from the customer order_item statuses, so the existing
 * seller/supplier status flows remain the single source of truth.
 */
class ProcurementService
{
    /**
     * The settlement states a procurement order can be in.
     *
     * reconciled  - the settlement was made by this call.
     * settled     - the supplier was already credited, nothing to do.
     * outstanding - the order is delivered, the settlement marker is missing
     *               and the ledger shows no supplier credit, so the
     *               settlement can still be made.
     * ambiguous   - the settlement marker is missing but the ledger already
     *               shows a supplier credit, so the correct financial state
     *               cannot be derived. The balance is left alone and the
     *               record is reported for manual review.
     * ineligible  - the order must not be settled (not delivered, or it has
     *               no supplier).
     */
    public const SETTLEMENT_RECONCILED = 'reconciled';
    public const SETTLEMENT_SETTLED = 'settled';
    public const SETTLEMENT_OUTSTANDING = 'outstanding';
    public const SETTLEMENT_AMBIGUOUS = 'ambiguous';
    public const SETTLEMENT_INELIGIBLE = 'ineligible';

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
        // The procurement state and the supplier source stock move together:
        // a failure while the stock is committed or released must not leave a
        // procurement order that no longer matches its reservation.
        $result = DB::transaction(function () use ($po) {
            $locked = ProcurementOrder::whereKey($po->getKey())
                ->with('items.orderItem')
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return null;
            }

            $previous = $locked->status;

            foreach ($locked->items as $poItem) {
                $orderItemStatus = $poItem->orderItem?->status ?? 'pending';

                if ($poItem->status !== $orderItemStatus) {
                    $poItem->status = $orderItemStatus;
                    $poItem->save();
                }
            }

            $target = $this->aggregateStatus($locked->items->map(fn ($pi) => $pi->status)->all());

            if ($target === $previous) {
                // The status did not change, so there is nothing to write and
                // no source stock to move. The settlement is still evaluated
                // below: the transaction above can commit before the
                // settlement transaction does, and a crash in between leaves
                // a delivered but unsettled procurement order behind.
                return [$locked, $target];
            }

            $locked->status = $target;

            if ($target === 'processing') {
                $locked->accepted_at = $locked->accepted_at ?? now();
            }
            if ($target === 'packed') {
                $locked->packed_at = $locked->packed_at ?? now();
            }
            if ($target === 'ready_for_pickup') {
                $locked->ready_at = $locked->ready_at ?? now();
            }
            if ($target === 'shipped') {
                $locked->shipped_at = $locked->shipped_at ?? now();
            }
            if ($target === 'delivered') {
                $locked->delivered_at = $locked->delivered_at ?? now();
            }
            if (in_array($target, ['cancelled', 'returned'], true)) {
                $locked->cancelled_at = $locked->cancelled_at ?? now();
            }

            $locked->save();

            // Commit reserved stock when first reaching a shipped/delivered state.
            if (in_array($target, ['shipped', 'delivered'], true)
                && ! in_array($previous, ['shipped', 'delivered'], true)) {
                app(SourceStockService::class)->commitForProcurement($locked);
            }

            // Release any reservations that were never committed.
            if (in_array($target, ['cancelled', 'returned'], true)
                && ! in_array($previous, ['shipped', 'delivered'], true)) {
                app(SourceStockService::class)->releaseForProcurement($locked);
            }

            return [$locked, $target];
        });

        if ($result === null) {
            return;
        }

        [$locked, $target] = $result;

        // The settlement owns its own outermost transaction, and with it its
        // own retry budget, so it must not run inside the transaction above:
        // Laravel cannot retry a nested transaction.

        // Settle the supplier once the procurement order is delivered. This is
        // deliberately not tied to a status change: the sync transaction and
        // the settlement transaction are two separate transactions, so a
        // crash between them leaves a delivered but unsettled procurement
        // order. Evaluating the settlement on every delivered synchronisation
        // makes that state repairable through the normal seller workflow.
        // reconcileSettlement() does nothing when the settlement is already
        // complete, so a repeated delivery action never credits twice.
        if ($target === 'delivered') {
            $this->settle($locked);
        }

        // On cancellation/return: reverse settlement if already paid out.
        if (in_array($target, ['cancelled', 'returned'], true) && $locked->settled_at !== null) {
            $this->reverseSettlement($locked);
        }
    }

    /**
     * Cancel every open procurement for an order and release reservations.
     * Used on order cancellation paths where item statuses are not driven
     * by the seller/supplier flows.
     *
     * This runs inside the transaction of the cancellation workflow, so it
     * must not ask for retries of its own: Laravel cannot retry a nested
     * transaction and would raise a DeadlockException instead. The enclosing
     * transaction owns the retry behaviour.
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

    /**
     * Settle the supplier for a delivered procurement order.
     *
     * The settlement is idempotent: the procurement order is locked, and a
     * procurement order that is already settled, that is not eligible, or
     * whose ledger state is ambiguous is never credited again.
     */
    private function settle(ProcurementOrder $po): void
    {
        $state = $this->reconcileSettlement($po);

        if ($state === self::SETTLEMENT_RECONCILED) {
            $this->notifySupplier($po, 'procurement_settlement',
                'Settlement for '.$po->po_number,
                route('supplier.wallet.index'));
        }
    }

    /**
     * Describe the settlement state of a procurement order without changing
     * anything.
     *
     * This is the read only half of the reconciliation: it tells an operator
     * whether a procurement order can be settled, is already settled, is not
     * eligible, or is in a state that needs manual review.
     */
    public function settlementState(ProcurementOrder $po): string
    {
        $current = ProcurementOrder::whereKey($po->getKey())->first();

        if ($current === null) {
            return self::SETTLEMENT_INELIGIBLE;
        }

        return $this->evaluateSettlement($current);
    }

    /**
     * Settle a procurement order when its settlement is still outstanding.
     *
     * This is the same path the normal application flow uses, so the balance
     * credit, the financial transaction ledger entry and the settlement marker
     * are written by exactly one piece of code. The procurement order is
     * locked for the whole operation, so two concurrent attempts serialise:
     * the second one sees the settlement marker and does nothing.
     *
     * @return string one of the SETTLEMENT_* states
     */
    public function reconcileSettlement(ProcurementOrder $po): string
    {
        $state = DB::transaction(function () use ($po) {
            $locked = ProcurementOrder::whereKey($po->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return self::SETTLEMENT_INELIGIBLE;
            }

            $state = $this->evaluateSettlement($locked);

            if ($state !== self::SETTLEMENT_OUTSTANDING) {
                return $state;
            }

            $balance = SupplierBalance::firstOrCreate(
                ['supplier_id' => $locked->supplier_id],
                ['balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'platform_fees' => 0]
            );

            $balance = SupplierBalance::whereKey($balance->getKey())->lockForUpdate()->first();

            $amount = (float) $locked->subtotal;

            $balance->increment('balance', $amount);
            $balance->increment('total_earned', $amount);

            Transaction::create([
                'accountable_type' => SupplierBalance::class,
                'accountable_id'   => $balance->id,
                'reference_type'   => ProcurementOrder::class,
                'reference_id'     => $locked->id,
                'type'             => 'credit',
                'amount'           => $amount,
                'description'      => 'Procurement settlement: '.$locked->po_number,
                'status'           => 'completed',
                'method'           => 'procurement',
            ]);

            $locked->update(['settled_at' => now()]);

            app(AuditService::class)->logFinancial('supplier_settlement', $locked, [
                'supplier_id' => $locked->supplier_id,
                'amount'      => $amount,
                'po_number'   => $locked->po_number,
            ]);

            return self::SETTLEMENT_RECONCILED;
        }, TransactionRetry::ATTEMPTS);

        if ($state === self::SETTLEMENT_AMBIGUOUS) {
            Log::warning('Procurement settlement is ambiguous and needs a manual review', [
                'procurement_order_id' => $po->id,
                'po_number'             => $po->po_number,
                'supplier_id'           => $po->supplier_id,
            ]);
        }

        return $state;
    }

    /**
     * The settlement state of a procurement order that is already read.
     *
     * A procurement order that is already settled is settled. A procurement
     * order that is not delivered, or that has no supplier, is not eligible.
     * A procurement order whose ledger already shows a supplier credit while
     * the settlement marker is missing is ambiguous: the money moved, but it
     * cannot be told from the ledger whether it was credited once or twice,
     * so the balance is left alone.
     */
    private function evaluateSettlement(ProcurementOrder $locked): string
    {
        if ($locked->settled_at !== null) {
            return self::SETTLEMENT_SETTLED;
        }

        if ($locked->status !== 'delivered' || $locked->supplier_id === null) {
            return self::SETTLEMENT_INELIGIBLE;
        }

        return abs($this->netSettledAmount($locked)) > 0.001
            ? self::SETTLEMENT_AMBIGUOUS
            : self::SETTLEMENT_OUTSTANDING;
    }

    /**
     * The amount the supplier was credited net for this procurement order.
     *
     * A settlement adds a credit entry and a reversal adds a refund entry, so
     * a settlement that was reversed nets out to zero and is still eligible
     * for a new settlement, while a credit without a matching reversal is a
     * net credit and therefore ambiguous.
     */
    private function netSettledAmount(ProcurementOrder $po): float
    {
        $entries = Transaction::where('reference_type', ProcurementOrder::class)
            ->where('reference_id', $po->id)
            ->whereIn('type', ['credit', 'refund'])
            ->get(['type', 'amount']);

        $net = 0.0;

        foreach ($entries as $entry) {
            $net += $entry->type === 'credit' ? (float) $entry->amount : -(float) $entry->amount;
        }

        return $net;
    }

    private function reverseSettlement(ProcurementOrder $po): void
    {
        DB::transaction(function () use ($po) {
            $locked = ProcurementOrder::whereKey($po->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->settled_at === null || $locked->supplier_id === null) {
                return;
            }

            $balance = SupplierBalance::where('supplier_id', $locked->supplier_id)
                ->lockForUpdate()
                ->first();

            if ($balance) {
                $amount = (float) $locked->subtotal;

                $balance->decrement('balance', $amount);
                $balance->decrement('total_earned', $amount);

                Transaction::create([
                    'accountable_type' => SupplierBalance::class,
                    'accountable_id'   => $balance->id,
                    'reference_type'   => ProcurementOrder::class,
                    'reference_id'     => $locked->id,
                    'type'             => 'refund',
                    'amount'           => $amount,
                    'description'      => 'Procurement reversal: '.$locked->po_number,
                    'status'           => 'completed',
                    'method'           => 'procurement',
                ]);

                app(AuditService::class)->logFinancial('supplier_settlement_reversal', $locked, [
                    'supplier_id' => $locked->supplier_id,
                    'amount'      => $amount,
                    'po_number'   => $locked->po_number,
                ]);
            }

            $locked->update(['settled_at' => null]);
        }, TransactionRetry::ATTEMPTS);
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