<?php

namespace App\Support;

/**
 * The shared budget for transaction retries.
 *
 * Laravel only retries a transaction when the failure is a recognised
 * concurrency error (an InnoDB deadlock, a lock wait timeout, a PostgreSQL
 * serialization failure or the SQLite "database is locked" error). Every
 * other failure is re-thrown on the first attempt.
 *
 * The cart mutations, the checkout and the order cancellation all take the
 * same rows in the same order, so they can deadlock against each other. They
 * therefore share one retry budget, and it is defined here so that a new
 * transaction cannot introduce a second, different one.
 *
 * The value is a deliberate trade-off: three attempts cover the rare case of
 * losing a deadlock race, while the worst case of a lock wait that never
 * clears stays bounded (a single lock wait can occupy a request for the
 * server's innodb_lock_wait_timeout, and there are three attempts).
 */
class TransactionRetry
{
    /**
     * The number of attempts a transaction gets before the failure is
     * reported to the caller.
     */
    public const ATTEMPTS = 3;
}
