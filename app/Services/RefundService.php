<?php

namespace App\Services;

use App\Exceptions\RefundStateException;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class RefundService
{
    /**
     * The state a card refund reaches when the payment provider confirmed the
     * refund but the local finalization has not happened yet.
     *
     * It is the durable marker that the money already moved: a refund in this
     * state is finished locally and never sent to the provider again.
     */
    public const STATUS_STRIPE_REFUNDED = 'stripe_refunded';

    /**
     * What the approval decided to do next.
     */
    private const OUTCOME_CASH = 'cash';

    private const OUTCOME_SEND = 'send';

    private const OUTCOME_FINALIZE = 'finalize';

    private const OUTCOME_DONE = 'done';
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

    /**
     * Approve a refund.
     *
     * A cash refund is only approved here; the cash is recorded later with
     * complete(). A card refund is approved and then sent to the payment
     * provider. The provider call happens outside any database transaction:
     * it is a network call, and holding a transaction open across it would
     * keep the refund row and the seller balance locked while the provider
     * answers. It also means a failure of the local finalization can no
     * longer undo a refund the provider already made.
     *
     * The provider call carries the refund's idempotency key, so a repeated
     * or concurrent approval cannot create a second provider refund: the
     * provider replays the refund it already created for that key.
     */
    public function approve(Refund $refund, int $approvedBy, string $notes = '', ?string $method = null): void
    {
        $outcome = DB::transaction(function () use ($refund, $approvedBy, $notes, $method) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            $order = $locked->order;
            $isStripe = $order !== null && $order->payment_method === 'stripe';

            switch ($locked->status) {
                case 'pending':
                    $attributes = [
                        'status'         => 'approved',
                        'approved_by'    => $approvedBy,
                        'approved_notes' => $notes,
                        'approved_at'    => now(),
                        'refund_method'  => $isStripe ? 'stripe' : $method,
                    ];

                    if ($isStripe) {
                        $attributes['stripe_idempotency_key'] = $this->idempotencyKeyFor($locked);
                    }

                    $locked->update($attributes);

                    app(AuditService::class)->logFinancial('refund_approved', $locked, [
                        'order_id'      => $locked->order_id,
                        'amount'        => $locked->amount,
                        'refund_method' => $locked->refund_method,
                    ]);

                    return $isStripe ? self::OUTCOME_SEND : self::OUTCOME_CASH;

                case 'approved':
                    if ($isStripe && blank($locked->stripe_idempotency_key)) {
                        // A refund that was approved before the key existed
                        // still needs one, or the provider would treat the
                        // next attempt as a new refund.
                        $locked->update([
                            'stripe_idempotency_key' => $this->idempotencyKeyFor($locked),
                        ]);
                    }

                    // A repeated approval. For a cash refund it changes
                    // nothing; for a card refund it resumes the provider
                    // call with the same idempotency key.
                    return $isStripe ? self::OUTCOME_SEND : self::OUTCOME_CASH;

                case self::STATUS_STRIPE_REFUNDED:
                    // The provider already refunded; only the local
                    // finalization is missing.
                    return self::OUTCOME_FINALIZE;

                case 'completed':
                    return self::OUTCOME_DONE;

                default:
                    throw new RefundStateException(
                        'A refund with the status "'.$locked->status.'" cannot be approved.'
                    );
            }
        });

        if ($outcome === self::OUTCOME_SEND) {
            $this->sendCardRefund($refund->fresh());
        } elseif ($outcome === self::OUTCOME_FINALIZE) {
            $this->finalizeCardRefund($refund->fresh());
        }
    }

    /**
     * Confirm that a manually settled refund (COD) has been completed.
     *
     * A card refund is not completed by an operator: the provider made the
     * refund, so the operation only finishes the local state, and it refuses
     * to complete a card refund the provider has not confirmed.
     */
    public function complete(Refund $refund, int $completedBy, string $notes = ''): void
    {
        DB::transaction(function () use ($refund, $completedBy, $notes) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'completed') {
                return;
            }

            if ($locked->status === self::STATUS_STRIPE_REFUNDED) {
                // The provider refunded and only the local finalization is
                // missing. Finishing it is the same operation the approval
                // performs.
                $locked->update([
                    'completed_by'     => $completedBy,
                    'completed_at'     => now(),
                    'completion_notes' => $notes,
                ]);

                $this->finalize($locked, $completedBy, $notes);

                return;
            }

            if ($locked->status !== 'approved') {
                throw new RefundStateException(
                    'A refund with the status "'.$locked->status.'" cannot be completed.'
                );
            }

            if ($locked->order !== null && $locked->order->payment_method === 'stripe') {
                throw new RefundStateException('A card refund is completed by the payment provider, not by an operator.');
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
        DB::transaction(function () use ($refund, $approvedBy, $reason) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'rejected') {
                return;
            }

            if ($locked->status !== 'pending') {
                throw new RefundStateException(
                    'A refund with the status "'.$locked->status.'" cannot be rejected.'
                );
            }

            $locked->update([
                'status'           => 'rejected',
                'approved_by'      => $approvedBy,
                'rejection_reason' => $reason,
                'rejected_at'      => now(),
            ]);
        });
    }

    /**
     * The idempotency key of a refund.
     *
     * The key describes the logical refund, so it is derived once and then
     * kept. A repeated attempt sends the same key, and the provider replays
     * the refund it already created for it instead of creating a second one.
     * The key carries no customer data.
     */
    private function idempotencyKeyFor(Refund $refund): string
    {
        $key = (string) $refund->stripe_idempotency_key;

        return $key !== '' ? $key : 'refund-'.$refund->getKey();
    }

    /**
     * Send the refund to the payment provider and record the outcome.
     *
     * The provider call happens outside any database transaction. The refund
     * row already carries the idempotency key, so a repeated or concurrent
     * attempt asks the provider for the same refund instead of creating a
     * second one.
     */
    private function sendCardRefund(Refund $refund): void
    {
        $order = $refund->order;
        $paymentIntentId = (string) ($order?->stripe_payment_intent_id ?? '');

        if ($paymentIntentId === '') {
            // Nothing can be refunded without the payment reference. The
            // refund stays approved, so it can be finished once the reference
            // is known.
            Log::warning('The card refund has no payment intent, so no refund was sent', [
                'order_id'  => $refund->order_id,
                'refund_id' => $refund->id,
            ]);

            return;
        }

        $key = $this->idempotencyKeyFor($refund);
        $amountInCents = (int) round((float) $refund->amount * 100);

        try {
            $providerRefundId = app(StripeRefundGateway::class)
                ->refund($paymentIntentId, $amountInCents, $key);
        } catch (Throwable $e) {
            // The provider did not confirm a refund. The refund stays
            // approved and can be tried again with the same key: for a key it
            // has seen, the provider replays the refund it already created
            // instead of creating a second one.
            Log::error('The card refund could not be sent', [
                'order_id'  => $refund->order_id,
                'refund_id' => $refund->id,
                'error'     => $e->getMessage(),
            ]);

            return;
        }

        // The provider refunded. That is recorded before the local
        // finalization, so a failure of the finalization leaves a durable
        // marker that the money already moved.
        $recorded = DB::transaction(function () use ($refund, $providerRefundId) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === self::STATUS_STRIPE_REFUNDED || $locked->status === 'completed') {
                return false;
            }

            if ($locked->status !== 'approved') {
                throw new RefundStateException('A refund that is not approved cannot be sent to the payment provider.');
            }

            $locked->update([
                'status'             => self::STATUS_STRIPE_REFUNDED,
                'stripe_refund_id'   => $providerRefundId,
                'stripe_refunded_at' => now(),
            ]);

            return true;
        });

        if (! $recorded) {
            return;
        }

        $this->finalizeCardRefund($refund->fresh());
    }

    /**
     * Finish a card refund locally.
     *
     * The provider already refunded, so only the local state is brought
     * forward: the order is marked refunded, the commission is reversed and
     * the audit trail is written. The transition and the local effects share
     * one transaction, so they are applied exactly once: a rollback undoes
     * them, and a repeated attempt finds the refund completed.
     */
    private function finalizeCardRefund(Refund $refund): void
    {
        DB::transaction(function () use ($refund) {
            $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'completed') {
                return;
            }

            if ($locked->status !== self::STATUS_STRIPE_REFUNDED) {
                throw new RefundStateException(
                    'A card refund can only be finalized after the payment provider refunded it.'
                );
            }

            $this->finalize($locked, (int) $locked->approved_by, (string) $locked->approved_notes);
        });
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
