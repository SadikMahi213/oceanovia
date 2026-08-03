<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($action = request('action')) {
            $query->where('action', $action);
        }

        if ($resourceType = request('resource_type')) {
            $query->where('resource_type', $resourceType);
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('resource_type', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);

        $actions = AuditLog::select('action')->distinct()->pluck('action')->sort();
        $resourceTypes = AuditLog::select('resource_type')->distinct()->pluck('resource_type')->sort();

        return view('admin.audit-logs.index', compact('logs', 'actions', 'resourceTypes'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
