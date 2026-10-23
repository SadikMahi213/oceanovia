<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CheckoutService;
use App\Services\CommissionService;
use App\Services\InventoryService;
use App\Services\ShippingService;
use App\Services\StockHoldService;
use App\Services\TaxService;
use App\Support\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /**
     * Reject cart items that are no longer purchasable.
     *
     * Only published products may enter a cart (see the cart endpoints), so a
     * cart item whose product was unpublished or removed afterwards must not
     * be silently ordered.
     */
    private function assertCartItemsAvailable(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product || $product->status !== 'published') {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items in your cart are no longer available. Please review your cart.',
                ]);
            }
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id' => 'required|exists:addresses,id',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => PaymentGateway::paymentMethodRule(),
        ]);

        // Everything below runs inside the transaction. The cart is read and
        // locked there, so a second checkout that waits for the same cart row
        // sees the cart the first request consumed and cannot create a
        // duplicate order from it.
        $order = DB::transaction(function () use ($request, $validated) {
            // Pessimistic lock to prevent double-order race condition. Taken
            // inside the transaction so it is held until the order commits.
            //
            // The parent cart row is locked first, then the cart item rows by
            // ascending id. This is the same protocol the cart endpoints use,
            // so a cart mutation and a checkout serialise on the same rows and
            // cannot observe each other's half finished work. The order, the
            // totals and the stock movement are all derived from these locked
            // rows.
            $cart = Cart::where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                return null;
            }

            $cart->setRelation(
                'items',
                $cart->items()
                    ->with('product.inventory')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
            );

            if ($cart->items->isEmpty()) {
                return null;
            }

            $this->assertCartItemsAvailable($cart);

            $shippingAddress = Address::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id']);
            $billingAddress = Address::where('user_id', auth()->id())->findOrFail($validated['billing_address_id']);

            $partition = app(InventoryService::class)->partitionForCart($cart);

            $confirmedSubtotal = 0.0;
            foreach ($cart->items as $item) {
                $confirmedSubtotal += (float) $item->unit_price * $partition->confirmedQuantity($item->id);
            }
            $subtotal = round($confirmedSubtotal, 2);

            $shippingCart = new Cart();
            $shippingItems = collect();
            foreach ($cart->items as $item) {
                $confirmedQty = $partition->confirmedQuantity($item->id);
                if ($confirmedQty <= 0) {
                    continue;
                }
                $ci = new CartItem();
                $ci->product_id = $item->product_id;
                $ci->quantity = $confirmedQty;
                $ci->unit_price = $item->unit_price;
                $ci->setRelation('product', $item->product);
                $shippingItems->push($ci);
            }
            $shippingCart->setRelation('items', $shippingItems);

            $shippingService = app(ShippingService::class);
            $shippingCost = $shippingService->calculate($shippingCart, $shippingAddress)['total'];
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
                'payment_status' => 'pending',
                'shipping_address_id' => $validated['shipping_address_id'],
                'billing_address_id' => $validated['billing_address_id'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                $heldQty = $partition->heldQuantity($item->id);
                $confirmedQty = $partition->confirmedQuantity($item->id);

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
                    'held_quantity' => $heldQty,
                    'stock_held_at' => $heldQty > 0 ? now() : null,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->unit_price * $confirmedQty,
                    'status' => $heldQty > 0 ? OrderItem::STATUS_AWAITING_STOCK : 'pending',
                ]);
            }

            app(CommissionService::class)->createCommissions($order);

            $cart->items()->delete();
            $cart->delete();

            $hasConfirmedItem = $order->items()
                ->where('status', '!=', OrderItem::STATUS_AWAITING_STOCK)
                ->exists();

            if ($validated['payment_method'] === 'cod' && $hasConfirmedItem) {
                app(CheckoutService::class)->finaliseCodOrder($order);
            }

            return $order;
        }, CheckoutService::TRANSACTION_ATTEMPTS);

        if ($order === null) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $order->load('items');

        // External side effects only run once the transaction has committed.
        // They are isolated from the checkout: the order is already saved, so
        // a failing notification or audit job must not fail the request.
        app(CheckoutService::class)->dispatchPostCommitEffects($order);

        foreach ($order->items()->where('status', OrderItem::STATUS_AWAITING_STOCK)->get() as $heldItem) {
            app(StockHoldService::class)->notifyHeldItem($heldItem);
        }

        if ($validated['payment_method'] === 'stripe' && (float) $order->total > 0) {
            return response()->json([
                'message' => 'Order created successfully.',
                'data' => $order,
                'payment_url' => route('checkout.index').'?order='.$order->id,
            ], 201);
        }

        if ($validated['payment_method'] === 'stripe') {
            return response()->json([
                'message' => 'Order created. Some items are awaiting stock confirmation.',
                'data' => $order,
            ], 201);
        }

        return response()->json([
            'message' => 'Order placed successfully. Payment will be collected on delivery.',
            'data' => $order,
            'payment_url' => route('orders.show', $order),
        ], 201);
    }
}
