<?php

namespace App\Services;

use RuntimeException;
use Stripe\StripeClient;
use Throwable;

/**
 * The card refund that goes to the payment provider.
 *
 * The call carries an idempotency key, so the provider answers a repeated
 * attempt with the refund it already created for that key instead of creating
 * a second one. That is what makes a repeated, retried or concurrent approval
 * of the same logical refund safe.
 */
class StripeRefundGateway
{
    /**
     * Refund an amount of a payment intent and return the provider's refund id.
     *
     * The same idempotency key always describes the same refund, so a retry
     * after an ambiguous answer returns the existing refund rather than
     * creating a second one.
     *
     * @throws RuntimeException when the provider refuses the refund or cannot
     *                          be reached. The caller must not treat that as a
     *                          successful refund.
     */
    public function refund(string $paymentIntentId, int $amountInCents, string $idempotencyKey): string
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new RuntimeException('The card gateway is not configured.');
        }

        try {
            $stripe = new StripeClient($secret);

            $refund = $stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount'         => $amountInCents,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('The card gateway did not confirm the refund: '.$e->getMessage(), 0, $e);
        }

        $id = (string) ($refund->id ?? '');

        if ($id === '') {
            throw new RuntimeException('The card gateway returned a refund without an id.');
        }

        return $id;
    }
}
