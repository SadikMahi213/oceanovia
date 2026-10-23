<?php

namespace App\Services;

use App\Exceptions\RefundStateException;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundReversal;
use App\Models\User;
use App\Support\RefundCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

            $item = $orderItemId !== null
                ? $order->items()->whereKey($orderItemId)->firstOrFail()
                : null;

            if ($amount === null) {
                $amount = $item ? (float) $item->subtotal : (float) $order->total;
            }

            $amount = round((float) $amount, 2);
            $refundable = $this->remainingRefundable($order, $item);

            if ($amount > $refundable) {
                throw ValidationException::withMessages([
                    'amount' => 'The refund amount exceeds what is still refundable for this order.',
                ]);
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
     * How much of an order (or of one item) can still be refunded, taking
     * every refund that was approved or completed into account.
     */
    private function remainingRefundable(Order $order, ?OrderItem $item = null): float
    {
        $query = Refund::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved', self::STATUS_STRIPE_REFUNDED, 'completed']);

        if ($item !== null) {
            $query->where('order_item_id', $item->id);
            $alreadyRequested = (float) $query->sum('amount');
            $ceiling = (float) $item->subtotal;
        } else {
            $alreadyRequested = (float) $query->sum('amount');
            $ceiling = (float) $order->items->sum('subtotal');
        }

        return max(0.0, round($ceiling - $alreadyRequested, 2));
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

        $this->reverseSellerCredits($refund, $order);

        $reversedItemIds = RefundReversal::where('refund_id', $refund->getKey())
            ->pluck('order_item_id')
            ->all();

        if ($this->isFullyRefunded($order, $refund, $reversedItemIds)) {
            $order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
        }

        $reversedTotal = RefundReversal::where('refund_id', $refund->getKey())->sum('refund_amount');

        $refund->update([
            'status' => 'completed',
            'reversed_amount' => $reversedTotal,
        ]);

        app(AuditService::class)->logFinancial('refund_completed', $refund, [
            'order_id'         => $refund->order_id,
            'order_item_id'    => $refund->order_item_id,
            'amount'           => $refund->amount,
            'reversed_amount'  => (float) $reversedTotal,
            'refund_method'    => $refund->refund_method,
            'completed_by'     => $by,
            'completion_notes' => $notes,
        ]);
    }

    /**
     * Whether refunding the given items leaves nothing of the order
     * outstanding. Only then is the whole order marked refunded.
     */
    private function isFullyRefunded(Order $order, Refund $refund, array $reversedItemIds): bool
    {
        $items = $order->items;

        if ($items->isEmpty()) {
            return true;
        }

        $reversedByItem = RefundReversal::whereIn('order_item_id', $items->pluck('id'))
            ->selectRaw('order_item_id, SUM(refund_amount) as total')
            ->groupBy('order_item_id')
            ->pluck('total', 'order_item_id');

        foreach ($items as $item) {
            $reversed = (float) ($reversedByItem[$item->id] ?? 0);

            if (round($reversed, 2) < round((float) $item->subtotal, 2)) {
                return false;
            }
        }

        return true;
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
    /**
     * Reconcile a refund the payment provider reports through a webhook
     * ("charge.refunded").
     *
     * The provider already moved the money, so the local state is brought
     * forward without sending anything back to the provider. When a local
     * refund already describes the provider refund, it is finalized. When
     * the provider refunded outside the local workflow (for example from the
     * Stripe dashboard), a completed refund row is created so the books stay
     * consistent.
     */
    public function recordProviderRefund(
        string $providerRefundId,
        string $paymentIntentId,
        int $amountInCents,
        ?string $currency = null,
    ): void {
        $existing = Refund::where('stripe_refund_id', $providerRefundId)->first();

        if ($existing !== null) {
            $this->resumeProviderRefund($existing);

            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($order === null) {
            Log::warning('A provider refund has no matching order', [
                'stripe_refund_id' => $providerRefundId,
                'payment_intent'   => $paymentIntentId,
            ]);

            return;
        }

        $refund = Refund::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('created_at')
            ->first();

        if ($refund !== null) {
            // The local workflow already describes this refund. Record the
            // provider identity and the fact the provider refunded, then
            // resume the finalization.
            DB::transaction(function () use ($refund, $providerRefundId) {
                $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

                $attributes = ['stripe_refunded_at' => now()];

                if (blank($locked->stripe_refund_id)) {
                    $attributes['stripe_refund_id'] = $providerRefundId;
                }

                if (in_array($locked->status, ['pending', 'approved'], true)) {
                    $attributes['status'] = self::STATUS_STRIPE_REFUNDED;
                }

                $locked->update($attributes);
            });

            $this->resumeProviderRefund($refund->fresh());

            return;
        }

        $amount = round($amountInCents / 100, 2);

        $refund = Refund::create([
            'order_id'           => $order->id,
            'user_id'            => $order->user_id,
            'amount'             => $amount,
            'reason'             => 'Refund reported by the payment provider',
            'status'             => self::STATUS_STRIPE_REFUNDED,
            'refund_method'      => 'stripe',
            'stripe_refund_id'   => $providerRefundId,
            'stripe_refunded_at' => now(),
            'approved_at'        => now(),
        ]);

        $this->finalizeCardRefund($refund->fresh());
    }

    /**
     * Bring a provider-confirmed refund to its local final state.
     */
    private function resumeProviderRefund(Refund $refund): void
    {
        if (in_array($refund->status, [self::STATUS_STRIPE_REFUNDED], true)) {
            $this->finalizeCardRefund($refund);

            return;
        }

        if ($refund->status === 'completed') {
            return;
        }

        if ($refund->status === 'approved') {
            DB::transaction(function () use ($refund) {
                $locked = Refund::whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

                if ($locked->status === 'approved') {
                    $locked->update(['status' => self::STATUS_STRIPE_REFUNDED]);
                }
            });

            $this->finalizeCardRefund($refund->fresh());
        }
    }

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

    /**
     * Reverse the seller credits that the refund covers.
     *
     * The refund is allocated to the order's items proportionally, so a
     * partial refund reverses only the share of an item it covers, and an
     * item can never be reversed for more than its value. Every reversal is
     * recorded as a RefundReversal and, when a seller credit is actually
     * given back, as a ledgered debit, so a repeated finalization cannot
     * reverse the same money twice.
     */
    private function reverseSellerCredits(Refund $refund, Order $order): void
    {
        if (RefundReversal::where('refund_id', $refund->getKey())->exists()) {
            $this->reverseCommissions($order, $this->reversedItemIdsFor($refund));

            return;
        }

        $allocations = app(RefundCalculator::class)->allocate($order, $refund);
        $payoutService = app(PayoutService::class);

        foreach ($allocations as $allocation) {
            /** @var OrderItem $item */
            $item = $allocation['item'];

            RefundReversal::create([
                'refund_id'      => $refund->getKey(),
                'order_item_id'  => $item->id,
                'seller_id'      => $item->seller_id ?? (int) $order->items->firstWhere('id', $item->id)?->seller_id,
                'refund_amount'  => $allocation['refund_amount'],
                'net_amount'     => $allocation['net_amount'],
            ]);

            if (! $item->seller_id || $allocation['net_amount'] <= 0) {
                continue;
            }

            $balance = $payoutService->getBalance($item->seller_id);

            // A seller can only give back money that is still on the
            // balance. The uncredited part of a COD order is not clawed
            // back into a negative balance.
            $reversal = min($allocation['net_amount'], max(0.0, (float) $balance->balance));

            if ($reversal <= 0) {
                continue;
            }

            $payoutService->reverse($item->seller_id, $reversal, [
                'reference_type' => Refund::class,
                'reference_id'   => $refund->getKey(),
                'description'    => 'Refund reversal',
            ]);
        }

        $this->reverseCommissions($order, $this->reversedItemIdsFor($refund));
    }

    /**
     * Mark a commission reversed once its item has been refunded in full.
     */
    private function reverseCommissions(Order $order, array $reversedItemIds): void
    {
        $reversedByItem = RefundReversal::whereIn('order_item_id', $reversedItemIds)
            ->selectRaw('order_item_id, SUM(refund_amount) as total')
            ->groupBy('order_item_id')
            ->pluck('total', 'order_item_id');

        foreach ($order->items->whereIn('id', $reversedItemIds) as $item) {
            $reversed = (float) ($reversedByItem[$item->id] ?? 0);

            if (round($reversed, 2) < round((float) $item->subtotal, 2)) {
                continue;
            }

            Commission::where('order_item_id', $item->id)
                ->where('status', 'pending')
                ->update(['status' => 'reversed']);
        }
    }

    private function reversedItemIdsFor(Refund $refund): array
    {
        return RefundReversal::where('refund_id', $refund->getKey())
            ->pluck('order_item_id')
            ->all();
    }
}
