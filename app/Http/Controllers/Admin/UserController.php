<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        if (!in_array($sortField, $allowed)) $sortField = 'created_at';
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $users = $query->paginate(15);

        return view('admin.users.index', compact('users', 'sortField', 'sortDir'));
    }
}
