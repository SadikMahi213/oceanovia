<?php

namespace Tests\Support;

use App\Services\StripeRefundGateway;
use RuntimeException;

/**
 * A payment provider that answers like the real one for the property that
 * matters for the refund idempotency: the same idempotency key always
 * returns the same refund, and a different key creates a new one.
 *
 * The real provider is not exercised by the test suite: the suite proves the
 * behaviour of the application, not the behaviour of Stripe.
 */
class FakeStripeRefundGateway extends StripeRefundGateway
{
    /** @var array<int, array{key: string, intent: string, amount: int}> */
    public array $calls = [];

    /** @var array<string, string> */
    public array $refunds = [];

    public ?RuntimeException $failure = null;

    public function refund(string $paymentIntentId, int $amountInCents, string $idempotencyKey): string
    {
        $this->calls[] = [
            'key'    => $idempotencyKey,
            'intent' => $paymentIntentId,
            'amount' => $amountInCents,
        ];

        if ($this->failure !== null) {
            throw $this->failure;
        }

        if (! isset($this->refunds[$idempotencyKey])) {
            $this->refunds[$idempotencyKey] = 're_test_'.(count($this->refunds) + 1);
        }

        return $this->refunds[$idempotencyKey];
    }
}
