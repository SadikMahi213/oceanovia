<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendAdminEmail;
use App\Models\EmailLog;
use App\Models\Order;
use App\Support\AdminRecipient;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendOrderPlacedNotification
{
    /**
     * Queue the order confirmation for the customer and a new-order alert for
     * admins. Uses the existing EmailLog + SendAdminEmail pipeline and is
     * idempotent per recipient and order so duplicate event dispatches (the
     * event is emitted both directly and from ProcessOrder) send one email.
     *
     * The idempotency is enforced by the database: every log row carries a
     * deterministic idempotency key, and the unique index on that column
     * turns a concurrent duplicate into a no-op. A read-then-write check
     * cannot do that, because two concurrent requests both see "no log yet".
     *
     * The order is already committed when this listener runs, so a failure
     * here must never propagate to the caller. It is logged instead, and the
     * log row is never left looking like a successful delivery.
     */
    public function handle(OrderPlaced $event): void
    {
        try {
            $this->notify($event);
        } catch (\Throwable $e) {
            // The fallback must not use the database: a failing database
            // write is the most likely cause of this failure.
            Log::error('Order placed notification failed', [
                'order_id' => $event->order->id,
                'exception' => $e,
            ]);
        }
    }

    private function notify(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing('user');
        $orderNumber = $order->order_number ?? ('#'.$order->id);

        $customer = $order->user;

        if ($customer !== null && $customer->email) {
            $subject = "Oceanovia — Order {$orderNumber} confirmed";

            $this->queue($customer->id, $customer->email, $customer->name, $subject, "Hello {$customer->name},"."\n\n"
                ."Thank you for your order {$orderNumber} placed on ".$order->created_at->format('F d, Y')."."."\n\n"
                .'Order total: $'.number_format((float) $order->total, 2)."\n"
                .'Payment method: '.strtoupper((string) $order->payment_method)."\n\n"
                ."We will keep you updated as your order progresses.\n\n"
                ."Thank you for choosing\nOceanovia.com.", $this->idempotencyKey($order, 'customer'));
        }

        $admin = AdminRecipient::resolve();

        if ($admin !== null && $admin['email']) {
            $adminSubject = "Oceanovia — New order {$orderNumber} placed";

            $adminBody = 'A new order was placed.'."\n\n"
                ."Order: {$orderNumber}"."\n"
                .'Customer: '.($customer?->name ?? '—')."\n"
                .'Total: $'.number_format((float) $order->total, 2)."\n"
                .'Payment method: '.strtoupper((string) $order->payment_method);

            $this->queue($admin['user_id'], $admin['email'], $admin['name'], $adminSubject, $adminBody, $this->idempotencyKey($order, 'admin'));
        }
    }

    /**
     * The deterministic identity of one logical notification.
     *
     * The order number is deliberately not part of the key: it is a display
     * value, while the order id and the audience identify the notification
     * itself. The customer confirmation and the admin alert are two distinct
     * notifications, so they carry distinct keys.
     */
    private function idempotencyKey(Order $order, string $audience): string
    {
        return 'order-placed:'.$order->id.':'.$audience;
    }

    private function queue(?int $userId, string $email, string $name, string $subject, string $body, string $idempotencyKey): void
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
        } catch (UniqueConstraintViolationException $e) {
            if (! $this->isIdempotencyConflict($e)) {
                // A different constraint failed. That is a real error, not a
                // duplicate notification, so it must not be swallowed.
                throw $e;
            }

            // The same notification was already queued by another request.
            return;
        }

        try {
            SendAdminEmail::dispatch($log);
        } catch (\Throwable $e) {
            // The email could not be queued. Record the failure instead of
            // leaving the row looking like a delivery that is still pending.
            EmailLog::whereKey($log->id)->update([
                'status'        => 'failed',
                'error_message' => Str::limit($e->getMessage(), 1000),
            ]);

            throw $e;
        }
    }

    /**
     * Whether the violated constraint is the idempotency key index.
     *
     * The exception carries the violated index on MySQL and PostgreSQL, and
     * the violated columns on SQLite and PostgreSQL.
     */
    private function isIdempotencyConflict(UniqueConstraintViolationException $e): bool
    {
        if (in_array('idempotency_key', $e->columns, true)) {
            return true;
        }

        return str_contains((string) $e->index, 'idempotency_key');
    }
}
