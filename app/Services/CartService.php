<?php

namespace App\Services;

use App\Models\Cart;
use App\Support\TransactionRetry;
use Illuminate\Database\UniqueConstraintViolationException;

class CartService
{
    /**
     * The name of the unique index that guarantees one cart per user.
     */
    public const USER_ID_UNIQUE_INDEX = 'carts_user_id_unique';

    /**
     * Bounded retry attempts for a cart mutation transaction.
     *
     * Two requests that create the same user's cart at the same time can
     * deadlock on InnoDB: both take a gap lock on the missing cart row and
     * then insert into it, and MySQL picks one of them as the deadlock
     * victim. Laravel recognises that error as a concurrency error, so the
     * transaction is retried with the same budget the checkout uses. Every
     * attempt re-reads the cart and the cart item rows, so a retry is safe.
     */
    public const TRANSACTION_ATTEMPTS = TransactionRetry::ATTEMPTS;

    /**
     * Take the parent cart row lock for the given user, creating the cart row
     * when it does not exist yet.
     *
     * The checkout path locks the same parent row, so a cart mutation and a
     * checkout for the same user serialise on it. Child cart item rows are
     * locked afterwards, by ascending id, so both paths acquire the rows in
     * the same order and cannot deadlock against each other.
     */
    public function lockForUser(?int $userId): Cart
    {
        $cart = Cart::where('user_id', $userId)->lockForUpdate()->first();

        if ($cart) {
            return $cart;
        }

        return $this->createForUser($userId);
    }

    /**
     * Return the user's cart, creating it when it does not exist yet.
     *
     * This is the read path (the API cart endpoint). It deliberately takes no
     * row lock, so it never blocks a concurrent mutation; the unique index is
     * what makes the creation safe.
     */
    public function findOrCreateForUser(?int $userId): Cart
    {
        $cart = Cart::where('user_id', $userId)->first();

        if ($cart) {
            return $cart;
        }

        return $this->createForUser($userId);
    }

    /**
     * Create the cart row, recovering from a concurrent creation of the same
     * user's cart.
     *
     * Locking a row that does not exist yet does not prevent a second
     * transaction from inserting one. Under REPEATABLE READ the gap locks two
     * transactions take on the same range are compatible with each other, so
     * both of them can insert. The unique index on carts.user_id is the
     * invariant that actually prevents a duplicate, and the conflict it raises
     * is the signal that another request won the race.
     */
    private function createForUser(?int $userId): Cart
    {
        try {
            return Cart::create(['user_id' => $userId, 'session_id' => null]);
        } catch (UniqueConstraintViolationException $e) {
            if (! $this->isUserIdConflict($e)) {
                // A different constraint failed. That is a real error, not a
                // duplicate cart, so it must not be swallowed.
                throw $e;
            }

            // Another request created the cart first, so theirs is the cart.
            // The read is a locking one, because a plain read inside an open
            // transaction would use the snapshot and could miss the row the
            // other transaction just committed.
            $cart = Cart::where('user_id', $userId)->lockForUpdate()->first();

            if (! $cart) {
                // The conflicting row cannot be read back, so there is no cart
                // this request can use. Report the failure instead of
                // returning a fabricated cart.
                throw $e;
            }

            return $cart;
        }
    }

    /**
     * Whether the violated constraint is the one cart per user index.
     *
     * The exception carries the violated columns on SQLite and PostgreSQL and
     * the violated index name on MySQL and PostgreSQL.
     */
    private function isUserIdConflict(UniqueConstraintViolationException $e): bool
    {
        if (in_array('user_id', $e->columns, true)) {
            return true;
        }

        return str_contains((string) $e->index, self::USER_ID_UNIQUE_INDEX);
    }
}
