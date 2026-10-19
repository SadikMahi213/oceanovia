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

    public function approve(Refund $refund, int $approvedBy, string $notes = '', ?string $method = null): void
    {
        DB::transaction(function () use ($refund, $approvedBy, $notes, $method) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                return;
            }

            $order = $locked->order;
            $isStripe = $order->payment_method === 'stripe';

            $locked->update([
                'status'         => 'approved',
                'approved_by'    => $approvedBy,
                'approved_notes' => $notes,
                'approved_at'    => now(),
                'refund_method'  => $isStripe ? 'stripe' : $method,
            ]);

            if ($isStripe) {
                if ($this->processStripeRefund($order, $locked)) {
                    $this->finalize($locked, $approvedBy, $notes);
                }

                return;
            }

            app(AuditService::class)->logFinancial('refund_approved', $locked, [
                'order_id'      => $locked->order_id,
                'amount'        => $locked->amount,
                'refund_method' => $method,
            ]);
        });
    }

    /**
     * Confirm that a manually settled refund (COD) has been completed.
     */
    public function complete(Refund $refund, int $completedBy, string $notes = ''): void
    {
        DB::transaction(function () use ($refund, $completedBy, $notes) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'approved') {
                return;
            }

            $locked->update([
                'completed_by'     => $completedBy,
                'completed_at'     => now(),
                'completion_notes' => $notes,
            ]);

            $this->finalize($locked, $completedBy, $notes);
        });
    }

    private function finalize(Refund $refund, int $by, string $notes = ''): void
    {
        $order = $refund->order;

        if ($order->status !== 'refunded') {
            $order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
            $this->reverseCommission($order, $refund->order_item_id);
        }

        $refund->update(['status' => 'completed']);

        app(AuditService::class)->logFinancial('refund_completed', $refund, [
            'order_id'         => $refund->order_id,
            'order_item_id'    => $refund->order_item_id,
            'amount'           => $refund->amount,
            'refund_method'    => $refund->refund_method,
            'completed_by'     => $by,
            'completion_notes' => $notes,
        ]);
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

    private function processStripeRefund(Order $order, Refund $refund): bool
    {
        if ($order->payment_method !== 'stripe' || !$order->stripe_payment_intent_id) {
            return false;
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

            return true;
        } catch (\Exception $e) {
            Log::error('Stripe refund failed', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function reverseCommission(Order $order, ?int $orderItemId = null): void
    {
        $itemIds = $orderItemId !== null
            ? [$orderItemId]
            : $order->items->pluck('id')->all();

        Commission::whereIn('order_item_id', $itemIds)
            ->where('status', 'pending')
            ->update(['status' => 'reversed']);

        $payoutService = app(PayoutService::class);
        $commissionService = app(CommissionService::class);

        // Sellers are credited NET (subtotal − commission) on payment, so reverse the net credit.
        foreach ($order->items->whereIn('id', $itemIds) as $item) {
            if (!$item->seller_id) {
                continue;
            }

            $net = $commissionService->netForItem($item);
            $balance = $payoutService->getBalance($item->seller_id);

            $reversal = min($net, max(0.0, (float) $balance->balance));

            if ($reversal <= 0) {
                continue;
            }

            $balance->decrement('balance', $reversal);
            $balance->decrement('total_earned', $reversal);

            app(AuditService::class)->logFinancial('seller_credit_reversal', $balance, [
                'seller_id' => $item->seller_id,
                'amount'    => $reversal,
                'order_id'  => $order->id,
                'order_item_id' => $item->id,
            ]);
        }
    }
}
