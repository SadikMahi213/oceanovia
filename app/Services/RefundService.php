<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe as StripeGateway;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RefundService
{
    public function requestRefund(Order $order, User $user, string $reason, ?float $amount = null, ?int $orderItemId = null): Refund
    {
        return DB::transaction(function () use ($order, $user, $reason, $amount, $orderItemId) {
            if (!in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered'])) {
                throw new NotFoundHttpException('This order is not eligible for a refund.');
            }

            if ($amount === null) {
                $amount = $orderItemId
                    ? $order->items()->findOrFail($orderItemId)->subtotal
                    : $order->total;
            }

            return Refund::create([
                'order_id'      => $order->id,
                'order_item_id' => $orderItemId,
                'user_id'       => $user->id,
                'amount'        => $amount,
                'reason'        => $reason,
                'status'        => 'pending',
            ]);
        });
    }

    public function approve(Refund $refund, int $approvedBy, string $notes = ''): void
    {
        DB::transaction(function () use ($refund, $approvedBy, $notes) {
            $refund->update([
                'status'         => 'approved',
                'approved_by'    => $approvedBy,
                'approved_notes' => $notes,
                'approved_at'    => now(),
            ]);

            $order = $refund->order;
            $order->update(['status' => 'refunded', 'payment_status' => 'refunded']);

            $this->reverseCommission($order);

            // Process automatic Stripe refund
            $this->processStripeRefund($order, $refund);
        });
    }

    public function reject(Refund $refund, int $approvedBy, string $reason = ''): void
    {
        $refund->update([
            'status'           => 'rejected',
            'approved_by'      => $approvedBy,
            'rejection_reason' => $reason,
            'rejected_at'      => now(),
        ]);
    }

    private function processStripeRefund(Order $order, Refund $refund): void
    {
        if ($order->payment_method !== 'stripe' || !$order->stripe_payment_intent_id) {
            return;
        }

        try {
            StripeGateway::setApiKey(config('services.stripe.secret'));

            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripe->refunds->create([
                'payment_intent' => $order->stripe_payment_intent_id,
                'amount' => (int) ($refund->amount * 100),
            ]);

            Log::info('Stripe refund processed', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'amount' => $refund->amount,
                'payment_intent' => $order->stripe_payment_intent_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reverseCommission(Order $order): void
    {
        Commission::whereIn('order_item_id', $order->items->pluck('id'))
            ->where('status', 'pending')
            ->update(['status' => 'reversed']);

        $payoutService = app(PayoutService::class);
        $commissionService = app(CommissionService::class);

        // Sellers are credited NET (subtotal − commission) on payment, so reverse the net credit.
        foreach ($order->items as $item) {
            if (!$item->seller_id) {
                continue;
            }

            $net = $commissionService->netForItem($item);
            $balance = $payoutService->getBalance($item->seller_id);
            $balance->decrement('balance', $net);
            $balance->decrement('total_earned', $net);
        }
    }
}
