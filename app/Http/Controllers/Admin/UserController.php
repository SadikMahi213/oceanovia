<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role_type', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowed = ['name', 'email', 'role_type', 'status', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $users = $query->paginate(15);

        return view('admin.users.index', compact('users', 'sortField', 'sortDir'));
    }

    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    public function verify(User $user, Request $request): RedirectResponse
    {
        if ($user->email_verified_at) {
            return back()->with('info', $user->name.' is already verified.');
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        app(AuditService::class)->log('user.verified', $user, [
            'email' => $user->email,
            'role_type' => $user->role_type,
            'email_verified_at' => $user->email_verified_at->toIso8601String(),
        ]);

        return back()->with('success', $user->name.' has been verified successfully.');
    }

    public function updateStatus(User $user, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive,suspended'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change the status of your own account.');
        }

        if ($user->status === $validated['status']) {
            return back()->with('info', $user->name.' is already '.$validated['status'].'.');
        }

        $oldStatus = $user->status;
        $user->update(['status' => $validated['status']]);

        app(AuditService::class)->log('user.status.'.$validated['status'], $user, [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'reason' => $validated['reason'] ?? null,
        ]);

        $message = match ($validated['status']) {
            'active' => $user->name.' has been reactivated successfully.',
            'inactive' => $user->name.' has been deactivated.',
            'suspended' => $user->name.' has been suspended.',
            default => 'Account status updated.',
        };

        return back()->with('success', $message);
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        try {
            $user->tokens()->delete();
            $user->delete();

            app(AuditService::class)->log('user.deleted', $user, [
                'id' => $user->id,
                'email' => $user->email,
                'role_type' => $user->role_type,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not delete the user. No changes were made.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
