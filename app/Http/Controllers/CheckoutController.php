<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Jobs\ProcessOrder;
use App\Models\Cart;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CommissionService;
use App\Services\CouponService;
use App\Services\PayoutService;
use App\Services\TaxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe as StripeGateway;
use Stripe\Webhook;

class CheckoutController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $cart = Cart::with(['items.product.inventory', 'items.product.category'])
            ->where('user_id', auth()->id())
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $addresses = auth()->user()->addresses;

        return view('checkout.index', compact('cart', 'addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shipping_address_id' => 'required_without:shipping_first_name|exists:addresses,id',
            'shipping_first_name' => 'required_without:shipping_address_id|string|max:255',
            'shipping_last_name' => 'required_without:shipping_address_id|string|max:255',
            'shipping_phone' => 'required_without:shipping_address_id|string|max:20',
            'shipping_address_line1' => 'required_without:shipping_address_id|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required_without:shipping_address_id|string|max:255',
            'shipping_state' => 'required_without:shipping_address_id|string|max:255',
            'shipping_zip' => 'required_without:shipping_address_id|string|max:20',
            'shipping_country' => 'required_without:shipping_address_id|string|max:2',
            'billing_same' => 'boolean',
            'billing_address_id' => 'nullable|exists:addresses,id',
            'payment_method' => 'required|in:stripe,cod',
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();

        if ($request->filled('shipping_address_id')) {
            $shippingAddress = Address::where('user_id', $user->id)
                ->findOrFail($request->shipping_address_id);
        } else {
            $shippingAddress = $user->addresses()->create([
                'address_type' => 'shipping',
                'first_name' => $request->shipping_first_name,
                'last_name' => $request->shipping_last_name,
                'phone' => $request->shipping_phone,
                'address_line1' => $request->shipping_address_line1,
                'address_line2' => $request->shipping_address_line2,
                'city' => $request->shipping_city,
                'state' => $request->shipping_state,
                'zip' => $request->shipping_zip,
                'country' => $request->shipping_country,
            ]);
        }

        if ($request->boolean('billing_same')) {
            $billingAddress = $shippingAddress;
        } elseif ($request->filled('billing_address_id')) {
            $billingAddress = Address::where('user_id', $user->id)
                ->findOrFail($request->billing_address_id);
        } else {
            $billingAddress = $shippingAddress;
        }

        // Pessimistic lock to prevent double-order race condition
        $cart = Cart::with('items.product.inventory')
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $order = DB::transaction(function () use ($user, $cart, $shippingAddress, $billingAddress, $request) {
            $subtotal = $cart->total;
            $shippingCost = 0;
            $tax = 0;
            $discount = 0;
            $total = $subtotal + $shippingCost + $tax - $discount;

            $order = Order::create([
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'notes' => $request->notes,
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'supplier_id' => $product->inventory?->supplier_id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->unit_price * $item->quantity,
                ]);
            }

            // Calculate tax
            $taxService = app(TaxService::class);
            $taxResult = $taxService->calculate($subtotal, $shippingAddress->state);
            $tax = $taxResult['amount'];

            // Apply coupon if provided
            if ($request->filled('coupon_code')) {
                $couponService = app(CouponService::class);
                $coupon = $couponService->validate($request->coupon_code, $user, $subtotal);
                $discount = $couponService->calculateDiscount($coupon, $subtotal);
                $couponService->apply($coupon, $order, $user);
            }

            // Update order with calculated tax and discount
            $order->update([
                'tax'      => $tax,
                'discount' => $discount,
                'total'    => $subtotal + $shippingCost + $tax - $discount,
            ]);

            // Create commissions exactly once (idempotent)
            app(CommissionService::class)->createCommissions($order);

            // DO NOT credit seller balances here — moved to post-payment confirmation
            // Seller credit now happens in success() or webhook() after Stripe confirms payment

            // Decrement inventory to prevent overselling
            $this->decrementInventory($cart);

            $cart->items()->delete();

            return $order;
        });

        event(new OrderPlaced($order));

        if ($request->payment_method === 'stripe') {
            ProcessOrder::dispatch($order);

            return redirect()->route('checkout.stripe', $order);
        }

        // COD: set payment_status to 'pending' (not 'paid') until delivery confirmed
        $order->update([
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'confirmed_at' => now(),
        ]);

        ProcessOrder::dispatch($order);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order placed successfully! Payment will be collected on delivery.');
    }

    public function stripe(Order $order): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Guard against paying an order that is already paid, refunded, or cancelled
        if (in_array($order->payment_status, ['paid', 'refunded'], true)) {
            return redirect()->route('checkout.success', $order);
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order has been cancelled and can no longer be paid.');
        }

        StripeGateway::setApiKey(config('services.stripe.secret'));

        $lineItems = [];
        $itemTotal = 0;
        foreach ($order->items as $item) {
            $unitAmount = (int) round($item->unit_price * 100);
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product_name,
                        'metadata' => ['product_id' => (string) $item->product_id],
                    ],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => $item->quantity,
            ];
            $itemTotal += $unitAmount * $item->quantity;
        }

        // Ensure Stripe is charged exactly the order total (tax/discount/shipping adjustments)
        $orderTotal = (int) round($order->total * 100);
        if ($orderTotal !== $itemTotal) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Order adjustments (tax & shipping)',
                    ],
                    'unit_amount' => $orderTotal - $itemTotal,
                ],
                'quantity' => 1,
            ];
        }

        $session = StripeSession::create([
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'customer_email' => auth()->user()->email,
            'line_items' => $lineItems,
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'success_url' => route('checkout.success', $order) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', $order),
        ]);

        $order->update([
            'payment_status' => 'pending',
            'stripe_session_id' => $session->id,
        ]);

        return redirect()->away($session->url);
    }

    public function success(Order $order, Request $request): View|RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($request->session_id) {
            StripeGateway::setApiKey(config('services.stripe.secret'));

            try {
                $session = StripeSession::retrieve($request->session_id);

                $sessionOrderId = $session->client_reference_id
                    ?? ($session->metadata->order_id ?? null);

                // Verify the session actually belongs to this order to prevent cross-order spoofing
                if ($session->payment_status === 'paid'
                    && (string) $sessionOrderId === (string) $order->id
                    && (int) round($session->amount_total / 100 * 100) === (int) round($order->total * 100)) {
                    DB::transaction(function () use ($order, $session) {
                        $locked = Order::where('id', $order->id)->where('payment_status', '!=', 'paid')->lockForUpdate()->first();
                        if (!$locked) return;
                        $locked->update([
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                            'confirmed_at' => now(),
                            'stripe_payment_intent_id' => $session->payment_intent,
                            'stripe_session_id' => $session->id,
                        ]);

                        // Credit seller balances NOW — payment confirmed
                        $this->creditSellers($locked);
                    });
                }
            } catch (\Exception $e) {
                Log::error('Stripe verification failed: ' . $e->getMessage());
            }
        }

        return view('checkout.confirmation', compact('order'));
    }

    public function cancel(Order $order): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order has already been paid and cannot be cancelled.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Payment cancelled',
        ]);

        return redirect()->route('cart.index')->with('error', 'Payment was cancelled. Your cart items are still saved.');
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        StripeGateway::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook verification failed: ' . $e->getMessage());
            return response('Webhook signature verification failed', 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type]);

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? $session->client_reference_id;

            if ($orderId) {
                DB::transaction(function () use ($orderId, $session) {
                    $order = Order::where('id', $orderId)->where('payment_status', '!=', 'paid')->lockForUpdate()->first();
                    if ($order) {
                        $order->update([
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                            'confirmed_at' => now(),
                            'stripe_payment_intent_id' => $session->payment_intent,
                            'stripe_session_id' => $session->id,
                        ]);

                        // Credit seller balances NOW — Stripe confirmed payment
                        $this->creditSellers($order);
                    }
                });
            }
        }

        if ($event->type === 'charge.refunded') {
            $charge = $event->data->object;
            $paymentIntentId = $charge->payment_intent;

            $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($order && $order->payment_status !== 'refunded') {
                $order->update(['payment_status' => 'refunded']);
            }
        }

        return response('Webhook received', 200);
    }

    private function creditSellers(Order $order): void
    {
        $payoutService = app(PayoutService::class);
        $commissionService = app(CommissionService::class);
        $order->load('items');
        foreach ($order->items as $item) {
            if ($item->seller_id) {
                // Credit NET amount — commission is deducted from the seller's payout
                $net = $commissionService->netForItem($item);
                $payoutService->credit($item->seller_id, $net);
            }
        }
    }

    private function decrementInventory(\App\Models\Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $inventory = $item->product?->inventory;
            if (!$inventory) {
                continue;
            }

            $inventory = \App\Models\Inventory::whereKey($inventory->id)->lockForUpdate()->first();
            if (!$inventory) {
                continue;
            }

            if ($inventory->stock_quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Insufficient stock for \"{$item->product->name}\". Please adjust your cart.",
                ]);
            }

            $inventory->decrement('stock_quantity', $item->quantity);
        }
    }
}
