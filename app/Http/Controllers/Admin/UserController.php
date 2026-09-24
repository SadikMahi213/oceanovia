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

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->tokens()->delete();
        $user->delete();

        app(AuditService::class)->log('user.deleted', $user, [
            'id' => $user->id,
            'email' => $user->email,
            'role_type' => $user->role_type,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
