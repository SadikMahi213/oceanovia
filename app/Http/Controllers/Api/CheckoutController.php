<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ShippingService;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id' => 'required|exists:addresses,id',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|max:30',
        ]);

        $shippingAddress = Address::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id']);
        $billingAddress = Address::where('user_id', auth()->id())->findOrFail($validated['billing_address_id']);

        $cart = Cart::where('user_id', auth()->id())
            ->with('items.product.inventory')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $subtotal = $cart->total;
        $shippingService = app(ShippingService::class);
        $shippingCost = $shippingService->calculate($cart, $shippingAddress)['total'];
        $taxService = app(TaxService::class);
        $taxResult = $taxService->calculate($subtotal, $shippingAddress->state);
        $tax = $taxResult['amount'];
        $total = $subtotal + $shippingCost + $tax;

        $order = Order::create([
            'user_id' => auth()->id(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax' => $tax,
            'discount' => 0,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'unpaid',
            'shipping_address_id' => $validated['shipping_address_id'],
            'billing_address_id' => $validated['billing_address_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'seller_id' => $item->product->seller_id,
                'product_name' => $item->product->name,
                'sku' => $item->product->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ]);
        }

        $cart->items()->delete();
        $cart->delete();

        $order->load('items');

        $paymentUrl = route('checkout.index').'?order='.$order->id;

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order,
            'payment_url' => $paymentUrl,
        ], 201);
    }
}
