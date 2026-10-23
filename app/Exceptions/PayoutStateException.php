<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A payout operation was asked to make a transition its state does not allow.
 *
 * The payout states are guarded so that money only moves along the path the
 * application allows: a payout is approved while it is pending, it is
 * completed while it is approved, and it is rejected while it is pending. A
 * payout that was completed or rejected already must not restore the funds a
 * second time.
 */
class PayoutStateException extends RuntimeException
{
}
