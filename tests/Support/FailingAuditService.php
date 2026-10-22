<?php

namespace Tests\Support;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * An audit log that cannot record the completion of a refund, so the local
 * finalization of a refund fails after the payment provider already
 * refunded it.
 */
class FailingAuditService extends AuditService
{
    public function logFinancial(string $action, Model $resource, array $context = []): AuditLog
    {
        if ($action === 'refund_completed') {
            throw new RuntimeException('The audit log is not available.');
        }

        return parent::logFinancial($action, $resource, $context);
    }
}
