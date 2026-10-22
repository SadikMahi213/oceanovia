<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rule;

class PaymentGateway
{
    /**
     * Cash on delivery. Needs no external credentials, so it is always
     * available and is the default payment method.
     */
    public const COD = 'cod';

    /**
     * Card payment through Stripe. Needs a full Stripe configuration.
     */
    public const STRIPE = 'stripe';

    /**
     * Determine whether the card gateway is fully configured.
     *
     * A missing or blank value counts as unconfigured. Only the presence of
     * the values is inspected - no secret is ever returned or logged.
     */
    public static function stripeConfigured(): bool
    {
        return filled(config('services.stripe.key'))
            && filled(config('services.stripe.secret'));
    }

    /**
     * The payment methods that can currently be offered to a customer.
     *
     * @return array<int, string>
     */
    public static function availableMethods(): array
    {
        return self::stripeConfigured()
            ? [self::COD, self::STRIPE]
            : [self::COD];
    }

    /**
     * Determine whether a payment method can currently be used.
     */
    public static function isAvailable(?string $method): bool
    {
        return $method !== null && in_array($method, self::availableMethods(), true);
    }

    /**
     * The payment method to preselect in the checkout.
     *
     * A previously submitted method is only kept while it is still
     * available; otherwise cash on delivery is preselected.
     */
    public static function resolveSelection(?string $method): string
    {
        return self::isAvailable($method) ? $method : self::COD;
    }

    /**
     * The customer-safe message shown when an unavailable method is submitted.
     */
    public static function unavailableMessage(): string
    {
        return 'Card payment is currently unavailable. Please choose Cash on Delivery.';
    }

    /**
     * The shared validation rule for the payment_method field.
     *
     * Rejecting an unconfigured card method here keeps a forged submission
     * from creating an order, deducting inventory or clearing the cart.
     *
     * @return array<int, mixed>
     */
    public static function paymentMethodRule(): array
    {
        return [
            'required',
            Rule::in([self::COD, self::STRIPE]),
            function (string $attribute, mixed $value, Closure $fail): void {
                if ($value === self::STRIPE && ! self::stripeConfigured()) {
                    $fail(self::unavailableMessage());
                }
            },
        ];
    }
}
