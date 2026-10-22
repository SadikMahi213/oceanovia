<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Jobs\ProcessOrder;
use App\Models\Order;
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    /**
     * Bounded retry attempts for the checkout transaction.
     *
     * Laravel only retries a transaction when the failure is a recognised
     * concurrency error (InnoDB deadlock, lock wait timeout, SQLite
     * "database is locked"). Validation failures, insufficient stock and
     * every other exception are re-thrown immediately.
     *
     * The cart mutations share the same budget, because they take the same
     * parent cart lock and can hit the same InnoDB deadlock.
     */
    public const TRANSACTION_ATTEMPTS = TransactionRetry::ATTEMPTS;

    /**
     * Complete a cash on delivery order.
     *
     * Runs inside the checkout transaction so a failure here rolls the order,
     * its items, the inventory movement and the cart consumption back
     * together - the platform must never keep a confirmed order with
     * deducted stock but no procurement order or cash collection.
     */
    public function finaliseCodOrder(Order $order): void
    {
        $order->update([
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'confirmed_at' => now(),
        ]);

        app(ProcurementService::class)->createForOrder($order);

        app(CodCollectionService::class)->createForOrder($order);
    }

    /**
     * Run the work that must happen only after the checkout transaction has
     * committed: the order placed notification and the order processing job.
     *
     * The order is already committed when this runs, so a failure here must
     * not be reported as a failed checkout. Each side effect is isolated and
     * logged on its own, so a broken notification cannot hide the audit job
     * and a broken queue cannot fail the request.
     */
    public function dispatchPostCommitEffects(Order $order): void
    {
        try {
            event(new OrderPlaced($order));
        } catch (\Throwable $e) {
            Log::error('Order placed event dispatch failed after the checkout committed', [
                'order_id'   => $order->id,
                'exception'  => $e,
            ]);
        }

        try {
            ProcessOrder::dispatch($order);
        } catch (\Throwable $e) {
            Log::error('Order processing job dispatch failed after the checkout committed', [
                'order_id'   => $order->id,
                'exception'  => $e,
            ]);
        }
    }
}
