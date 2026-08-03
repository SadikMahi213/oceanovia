<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::byUser(auth()->id())
            ->with(['items'])
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(int $id): View
    {
        $order = Order::with([
            'items.product',
            'items.seller',
            'shippingAddress',
            'billingAddress',
        ])
            ->byUser(auth()->id())
            ->findOrFail($id);

        return view('orders.show', compact('order'));
    }
}
