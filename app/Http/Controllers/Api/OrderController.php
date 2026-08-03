<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::byUser(auth()->id())
            ->with('items')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $order = Order::byUser(auth()->id())
            ->with(['items.product', 'shippingAddress', 'billingAddress'])
            ->findOrFail($id);

        return response()->json(['data' => $order]);
    }
}
