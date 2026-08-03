<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(
        string $action,
        ?Model $resource = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id'  => $resource?->getKey(),
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }

    public function logFinancial(
        string $action,
        Model $resource,
        array $context = [],
    ): AuditLog {
        return $this->log("financial.{$action}", $resource, null, $context);
    }
}
