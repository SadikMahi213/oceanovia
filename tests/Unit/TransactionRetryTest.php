<?php

namespace Tests\Unit;

use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderCancellationService;
use App\Support\TransactionRetry;
use PHPUnit\Framework\TestCase;

/**
 * R3.1 - the transaction retry budget has one authoritative home.
 *
 * The cart mutations, the checkout and the order cancellation all take the
 * same rows in the same order, so they can deadlock against each other and
 * they share one retry budget. This test protects that policy: the budget
 * stays at three attempts and every transaction that uses it resolves to the
 * same value, so a new transaction cannot quietly introduce a second one.
 */
class TransactionRetryTest extends TestCase
{
    public function test_the_shared_retry_budget_is_three_attempts(): void
    {
        $this->assertSame(3, TransactionRetry::ATTEMPTS);
    }

    public function test_every_transaction_using_the_budget_resolves_to_the_shared_value(): void
    {
        $this->assertSame(TransactionRetry::ATTEMPTS, CartService::TRANSACTION_ATTEMPTS);
        $this->assertSame(TransactionRetry::ATTEMPTS, CheckoutService::TRANSACTION_ATTEMPTS);
        $this->assertSame(TransactionRetry::ATTEMPTS, OrderCancellationService::TRANSACTION_ATTEMPTS);
    }
}
