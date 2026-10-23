<?php

namespace App\Support;

/**
 * Result of partitioning a cart's requested quantities against the stock that
 * is actually available.
 *
 * confirmed: cart item id => quantity that was reserved and is billable.
 * held:      cart item id => shortfall that could not be reserved.
 *
 * The two maps are disjoint by cart item id, and for every item the sum of
 * its confirmed and held quantities equals the quantity that was requested.
 */
class StockPartition
{
    public function __construct(
        public readonly array $confirmed = [],
        public readonly array $held = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->confirmed === [] && $this->held === [];
    }

    public function allHeld(): bool
    {
        return $this->confirmed === [] && $this->held !== [];
    }

    public function hasHeld(): bool
    {
        return $this->held !== [];
    }

    public function confirmedQuantity(int $cartItemId): int
    {
        return (int) ($this->confirmed[$cartItemId] ?? 0);
    }

    public function heldQuantity(int $cartItemId): int
    {
        return (int) ($this->held[$cartItemId] ?? 0);
    }

    public function requestedQuantity(int $cartItemId): int
    {
        return $this->confirmedQuantity($cartItemId) + $this->heldQuantity($cartItemId);
    }
}
