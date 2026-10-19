<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendAdminEmail;
use App\Models\EmailLog;
use App\Support\AdminRecipient;

class SendOrderPlacedNotification
{
    /**
     * Queue the order confirmation for the customer and a new-order alert for
     * admins. Uses the existing EmailLog + SendAdminEmail pipeline and is
     * idempotent per recipient and order so duplicate event dispatches (the
     * event is emitted both directly and from ProcessOrder) send one email.
     */
    public function handle(OrderPlaced $event): void
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
                ."Thank you for choosing\nOceanovia.com.");
        }

        $admin = AdminRecipient::resolve();

        if ($admin !== null && $admin['email']) {
            $adminSubject = "Oceanovia — New order {$orderNumber} placed";

            $adminBody = 'A new order was placed.'."\n\n"
                ."Order: {$orderNumber}"."\n"
                .'Customer: '.($customer?->name ?? '—')."\n"
                .'Total: $'.number_format((float) $order->total, 2)."\n"
                .'Payment method: '.strtoupper((string) $order->payment_method);

            $this->queue($admin['user_id'], $admin['email'], $admin['name'], $adminSubject, $adminBody);
        }
    }

    private function queue(?int $userId, string $email, string $name, string $subject, string $body): void
    {
        $exists = EmailLog::query()
            ->where('user_id', $userId)
            ->where('subject', $subject)
            ->exists();

        if ($exists) {
            return;
        }

        $log = EmailLog::create([
            'user_id'         => $userId,
            'recipient_email' => $email,
            'recipient_name'  => $name,
            'subject'         => $subject,
            'message'         => $body,
            'body_type'       => 'text',
            'status'          => 'requested',
        ]);

        SendAdminEmail::dispatch($log);
    }
}
