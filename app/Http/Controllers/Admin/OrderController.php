<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowed = ['created_at', 'updated_at', 'total', 'status'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $orders = $query->paginate(15);

        return view('admin.orders.index', compact('orders', 'sortField', 'sortDir'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'shippingAddress', 'billingAddress']);
        return view('admin.orders.show', compact('order'));
    }
}
