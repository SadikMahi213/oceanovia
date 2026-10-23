<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function create(Order $order): View
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered'])) {
            abort(404, 'This order is not eligible for a refund.');
        }

        return view('refunds.create', compact('order'));
    }

    public function store(Request $request, Order $order, RefundService $refundService): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'reason'        => 'required|string|max:2000',
            'amount'        => 'nullable|numeric|min:0|max:' . $order->total,
            'order_item_id' => 'nullable|integer',
        ]);

        // The item must belong to this order: an unscoped reference would let
        // a customer ask for a refund against another order's item.
        $orderItemId = $validated['order_item_id'] ?? null;

        if ($orderItemId !== null && ! $order->items()->whereKey($orderItemId)->exists()) {
            throw ValidationException::withMessages([
                'order_item_id' => 'The selected item does not belong to this order.',
            ]);
        }

        try {
            $refundService->requestRefund(
                $order,
                auth()->user(),
                $validated['reason'],
                $validated['amount'] ?? null,
                $orderItemId
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Refund request submitted successfully.');
    }
}
