<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOrder;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CodCollectionService;
use App\Services\CommissionService;
use App\Services\ProcurementService;
use App\Services\ShippingService;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id' => 'required|exists:addresses,id',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:stripe,cod',
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

        $order = DB::transaction(function () use ($cart, $validated, $subtotal, $shippingCost, $tax, $total) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax' => $tax,
                'discount' => 0,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'shipping_address_id' => $validated['shipping_address_id'],
                'billing_address_id' => $validated['billing_address_id'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'supplier_id' => $product->supplierProduct?->supplier_id ?? $product->inventory?->supplier_id,
                    'supplier_product_id' => $product->supplier_product_id,
                    'unit_cost' => $product->supplier_product_id ? $product->sourcing_price : null,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            app(CommissionService::class)->createCommissions($order);

            $cart->items()->delete();
            $cart->delete();

            return $order;
        });

        $order->load('items');

        event(new OrderPlaced($order));

        if ($validated['payment_method'] === 'stripe') {
            ProcessOrder::dispatch($order);

            return response()->json([
                'message' => 'Order created successfully.',
                'data' => $order,
                'payment_url' => route('checkout.index').'?order='.$order->id,
            ], 201);
        }

        $order->update([
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'confirmed_at' => now(),
        ]);

        app(ProcurementService::class)->createForOrder($order);
        app(CodCollectionService::class)->createForOrder($order);
        ProcessOrder::dispatch($order);

        return response()->json([
            'message' => 'Order placed successfully. Payment will be collected on delivery.',
            'data' => $order,
            'payment_url' => route('orders.show', $order),
        ], 201);
    }
}
