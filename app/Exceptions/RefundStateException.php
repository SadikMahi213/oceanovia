<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A refund operation was asked to make a transition its state does not allow.
 *
 * The refund states are guarded so that money only moves along a path the
 * state machine allows: a rejected refund cannot be approved, and a card
 * refund cannot be completed before the payment provider confirmed it.
 */
class RefundStateException extends RuntimeException
{
}
