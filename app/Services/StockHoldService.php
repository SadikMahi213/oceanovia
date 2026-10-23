<?php

namespace App\Services;

use App\Jobs\SendAdminEmail;
use App\Models\Commission;
use App\Models\CodCollection;
use App\Models\EmailLog;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierProductStock;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\AdminRecipient;
use App\Support\RefundCalculator;
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StockHoldService
{
    public const STATUS_AWAITING_STOCK = OrderItem::STATUS_AWAITING_STOCK;

    /**
     * Notify the responsible seller/supplier and the ordering customer that an
     * item is held for stock confirmation.
     */
    public function notifyHeldItem(OrderItem $item): void
    {
        try {
            $item->loadMissing('order.user', 'product');

            $order = $item->order;
            $productName = $item->product_name;
            $orderNumber = $order->order_number ?? ('#'.$order->id);

            // Seller
            if ($item->seller_id) {
                $seller = User::find($item->seller_id);
                if ($seller !== null && $seller->email) {
                    $this->notifyInApp($seller->id, 'order_item_stock_hold',
                        "Stock check needed: {$productName}",
                        'alert-triangle', route('seller.orders.show', $order),
                        ['order_id' => $order->id, 'order_item_id' => $item->id]);
                    $this->queueEmail($seller->id, $seller->email, $seller->name,
                        "Oceanovia — Stock check needed for order {$orderNumber}",
                        "Hello {$seller->name},\n\n"
                        ."An item in order {$orderNumber} is awaiting stock confirmation.\n\n"
                        ."Item: {$productName}\n"
                        ."Quantity requested: {$item->quantity}\n"
                        ."Units held (shortfall): {$item->held_quantity}\n\n"
                        ."Please confirm or decline availability from your seller dashboard.\n\n"
                        ."Thank you,\nOceanovia.com.",
                        "stock-hold:{$item->id}:seller");
                }
            }

            // Supplier
            if ($item->supplier_id) {
                $supplier = User::find($item->supplier_id);
                if ($supplier !== null && $supplier->email) {
                    $this->notifyInApp($supplier->id, 'order_item_stock_hold',
                        "Stock check needed: {$productName}",
                        'alert-triangle', route('supplier.orders.show', $order),
                        ['order_id' => $order->id, 'order_item_id' => $item->id]);
                    $this->queueEmail($supplier->id, $supplier->email, $supplier->name,
                        "Oceanovia — Stock check needed for order {$orderNumber}",
                        "Hello {$supplier->name},\n\n"
                        ."An item in order {$orderNumber} is awaiting stock confirmation.\n\n"
                        ."Item: {$productName}\n"
                        ."Quantity requested: {$item->quantity}\n"
                        ."Units held (shortfall): {$item->held_quantity}\n\n"
                        ."Please confirm or decline availability from your supplier dashboard.\n\n"
                        ."Thank you,\nOceanovia.com.",
                        "stock-hold:{$item->id}:supplier");
                }
            }

            // Customer
            $customer = $order->user;
            if ($customer !== null && $customer->email) {
                $this->notifyInApp($customer->id, 'order_item_stock_hold',
                    "We're checking availability for {$productName}",
                    'clock', route('customer.orders.show', $order),
                    ['order_id' => $order->id, 'order_item_id' => $item->id]);
                $this->queueEmail($customer->id, $customer->email, $customer->name,
                    "Oceanovia — Checking availability for {$productName}",
                    "Hello {$customer->name},\n\n"
                    ."We're confirming availability for \"{$productName}\" in order {$orderNumber}.\n\n"
                    ."We will keep you updated as soon as the seller/supplier responds.\n\n"
                    ."Thank you for choosing\nOceanovia.com.",
                    "stock-hold:{$item->id}:customer");
            }
        } catch (\Throwable $e) {
            Log::error('Stock hold notification failed', [
                'order_item_id' => $item->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Attempt to source the held shortfall. Returns true when the item is
     * fully confirmed (held_quantity reaches 0).
     */
    public function confirm(OrderItem $item, int $actorId): bool
    {
        return DB::transaction(function () use ($item, $actorId) {
            $locked = OrderItem::whereKey($item->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status !== self::STATUS_AWAITING_STOCK) {
                return false;
            }

            $order = Order::whereKey($locked->order_id)->lockForUpdate()->first();
            if ($order === null) {
                return false;
            }

            $held = (int) $locked->held_quantity;
            $product = $locked->product;

            $reservedNow = 0;

            if ($held > 0) {
                if ($product !== null && $product->supplier_product_id) {
                    $stock = SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)
                        ->lockForUpdate()->first();
                    $available = $stock ? $stock->stock_quantity - $stock->reserved_quantity : 0;
                    $toReserve = min($held, $available);
                    if ($toReserve > 0 && $stock) {
                        $stock->increment('reserved_quantity', $toReserve);
                        $reservedNow = $toReserve;
                    }
                } elseif ($product !== null) {
                    $inventory = $product->inventory;
                    if ($inventory !== null) {
                        $row = Inventory::whereKey($inventory->id)->lockForUpdate()->first();
                        $available = $row ? (int) $row->stock_quantity : 0;
                        $toReserve = min($held, $available);
                        if ($toReserve > 0 && $row) {
                            $row->decrement('stock_quantity', $toReserve);
                            $reservedNow = $toReserve;
                        }
                    } else {
                        // Not stock-managed: confirm the whole shortfall.
                        $reservedNow = $held;
                    }
                } else {
                    $reservedNow = $held;
                }
            }

            $newHeld = $held - $reservedNow;

            if ($newHeld <= 0) {
                $locked->update([
                    'status'              => 'pending',
                    'held_quantity'       => 0,
                    'subtotal'            => round((float) $locked->unit_price * (int) $locked->quantity, 2),
                    'stock_confirmed_at'  => now(),
                ]);

                $order = $order->fresh(['items']);
                app(CommissionService::class)->createCommissions($order);
                app(ProcurementService::class)->createForOrder($order);

                if ($order->payment_method === 'cod') {
                    app(CodCollectionService::class)->createForOrder($order);
                }

                app(OrderTotalsService::class)->recompute($order);

                $this->notifyCustomerConfirmed($locked->fresh());

                return true;
            }

            $locked->update([
                'held_quantity' => $newHeld,
                'subtotal'      => round((float) $locked->subtotal + (float) $locked->unit_price * $reservedNow, 2),
            ]);

            return false;
        }, TransactionRetry::ATTEMPTS);
    }

    /**
     * Decline the held item: release only the reserved portion, cancel the
     * item, reverse pending commissions/COD/procurement, recompute totals,
     * refund if the order was paid, and notify the customer.
     */
    public function decline(OrderItem $item, int $actorId): void
    {
        DB::transaction(function () use ($item, $actorId) {
            $locked = OrderItem::whereKey($item->getKey())->lockForUpdate()->first();

            if ($locked === null || $locked->status !== self::STATUS_AWAITING_STOCK) {
                return;
            }

            $order = Order::whereKey($locked->order_id)->lockForUpdate()->first();
            if ($order === null) {
                return;
            }

            $product = $locked->product;
            $reservedQty = max(0, (int) $locked->quantity - (int) $locked->held_quantity);

            // Release only the actually-reserved portion.
            if ($reservedQty > 0) {
                if ($product !== null && $product->supplier_product_id) {
                    app(SourceStockService::class)->releaseReservedUnits(
                        (int) $product->supplier_product_id,
                        $reservedQty
                    );
                } elseif ($product !== null && $product->inventory !== null) {
                    Inventory::whereKey($product->inventory->id)
                        ->increment('stock_quantity', $reservedQty);
                }
            }

            $locked->update([
                'status'             => 'cancelled',
                'stock_declined_at'  => now(),
            ]);

            Commission::where('order_item_id', $locked->id)
                ->where('status', 'pending')
                ->update(['status' => 'reversed']);

            CodCollection::where('order_item_id', $locked->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            app(ProcurementService::class)->syncForOrder($order);

            // Refund only if money was actually taken for this item. A fully
            // held item has subtotal 0 and creates no refund.
            if ($order->payment_status === 'paid' && (float) $locked->subtotal > 0) {
                try {
                    $refund = app(RefundService::class)->requestRefund(
                        $order,
                        $order->user,
                        'Stock declined by seller/supplier',
                        (float) $locked->subtotal,
                        (int) $locked->id
                    );
                    app(RefundService::class)->approve($refund, $actorId, 'Stock declined');
                } catch (\Throwable $e) {
                    Log::error('Refund on stock decline failed', [
                        'order_item_id' => $locked->id,
                        'exception' => $e,
                    ]);
                }
            }

            app(OrderTotalsService::class)->recompute($order);

            $remainingActive = $order->items()
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->count();

            if ($remainingActive === 0) {
                $order->update([
                    'status'           => 'cancelled',
                    'cancelled_at'     => $order->cancelled_at ?? now(),
                    'cancellation_reason' => $order->cancellation_reason ?? 'Stock declined',
                ]);
            }

            $this->notifyCustomerDeclined($locked->fresh());
        }, TransactionRetry::ATTEMPTS);
    }

    // ─── Notifications ──────────────────────────────────────────────────────

    private function notifyCustomerConfirmed(OrderItem $item): void
    {
        $item->loadMissing('order.user');
        $order = $item->order;
        $customer = $order->user;
        if ($customer === null || ! $customer->email) {
            return;
        }

        $productName = $item->product_name;
        $orderNumber = $order->order_number ?? ('#'.$order->id);

        $this->notifyInApp($customer->id, 'order_item_stock_confirmed',
            "Good news: {$productName} is confirmed",
            'check-circle', route('customer.orders.show', $order),
            ['order_id' => $order->id, 'order_item_id' => $item->id]);

        $this->queueEmail($customer->id, $customer->email, $customer->name,
            "Oceanovia — {$productName} is now confirmed",
            "Hello {$customer->name},\n\n"
            ."Good news: \"{$productName}\" in order {$orderNumber} has been confirmed and is being prepared.\n\n"
            ."Thank you for choosing\nOceanovia.com.",
            "stock-confirmed:{$item->id}:customer");
    }

    private function notifyCustomerDeclined(OrderItem $item): void
    {
        $item->loadMissing('order.user');
        $order = $item->order;
        $customer = $order->user;
        if ($customer === null || ! $customer->email) {
            return;
        }

        $productName = $item->product_name;
        $orderNumber = $order->order_number ?? ('#'.$order->id);

        $this->notifyInApp($customer->id, 'order_item_stock_declined',
            "Update on {$productName}",
            'x-circle', route('customer.orders.show', $order),
            ['order_id' => $order->id, 'order_item_id' => $item->id]);

        $this->queueEmail($customer->id, $customer->email, $customer->name,
            "Oceanovia — Update on {$productName}",
            "Hello {$customer->name},\n\n"
            ."We're sorry: \"{$productName}\" in order {$orderNumber} could not be sourced and has been cancelled.\n\n"
            ."Any applicable refund will be processed according to your payment method.\n\n"
            ."Thank you for choosing\nOceanovia.com.",
            "stock-declined:{$item->id}:customer");
    }

    private function notifyInApp(int $userId, string $type, string $title, string $icon, string $link, array $data = []): void
    {
        $key = $type.':'.$userId.':'.($data['order_item_id'] ?? '');
        $id = Uuid::uuid5(Uuid::NAMESPACE_URL, $key)->toString();

        try {
            UserNotification::create([
                'id'               => $id,
                'type'             => $type,
                'notifiable_type'  => User::class,
                'notifiable_id'    => $userId,
                'data'             => $data,
                'title'            => $title,
                'icon'             => $icon,
                'link'             => $link,
            ]);
        } catch (\Throwable $e) {
            // Duplicate in-app notification (same deterministic id) is a no-op.
            if (! str_contains(strtolower($e->getMessage()), 'unique')) {
                throw $e;
            }
        }
    }

    private function queueEmail(?int $userId, string $email, string $name, string $subject, string $body, string $idempotencyKey): void
    {
        try {
            $log = EmailLog::create([
                'user_id'         => $userId,
                'recipient_email' => $email,
                'recipient_name'  => $name,
                'subject'         => $subject,
                'message'         => $body,
                'body_type'       => 'text',
                'status'          => 'requested',
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            if (! $this->isIdempotencyConflict($e)) {
                throw $e;
            }

            return;
        }

        try {
            SendAdminEmail::dispatch($log);
        } catch (\Throwable $e) {
            EmailLog::whereKey($log->id)->update([
                'status'        => 'failed',
                'error_message' => Str::limit($e->getMessage(), 1000),
            ]);
            throw $e;
        }
    }

    private function isIdempotencyConflict(\Illuminate\Database\UniqueConstraintViolationException $e): bool
    {
        if (in_array('idempotency_key', $e->columns, true)) {
            return true;
        }

        return str_contains((string) $e->index, 'idempotency_key');
    }
}
