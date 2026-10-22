<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CheckoutService;
use App\Services\CommissionService;
use App\Services\CouponService;
use App\Services\InventoryService;
use App\Services\OrderCancellationService;
use App\Services\PayoutService;
use App\Services\ProcurementService;
use App\Services\ShippingService;
use App\Services\TaxService;
use App\Support\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $addresses = auth()->user()->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        $shippingAddresses = $addresses->where('address_type', 'shipping')->values();
        $billingAddresses = $addresses->where('address_type', 'billing')->values();

        // Preserve legacy compatibility: when no shipping address exists yet,
        // the customer's own billing addresses may be used as shipping options.
        $shippingUsingBillingFallback = $shippingAddresses->isEmpty() && $billingAddresses->isNotEmpty();
        if ($shippingUsingBillingFallback) {
            $shippingAddresses = $billingAddresses;
        }

        // Pre-calculate shipping/tax for preview (using first eligible address if available)
        $previewAddress = $shippingAddresses->first();
        $shippingService = app(ShippingService::class);
        $taxService = app(TaxService::class);
        $shippingPreview = $shippingService->calculate($cart, $previewAddress);
        $taxPreview = $previewAddress ? $taxService->calculate($cart->total, $previewAddress->state) : ['amount' => 0, 'rate' => 0, 'name' => 'No Tax'];

        return view('checkout.index', compact(
            'cart',
            'shippingAddresses',
            'billingAddresses',
            'shippingUsingBillingFallback',
            'shippingPreview',
            'taxPreview'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $billingSame = $request->has('billing_same')
            ? $request->boolean('billing_same')
            : true;
        $hasSavedBillingAddress = ! $billingSame && $request->filled('billing_address_id');

        // Match the selector eligibility exactly: shipping addresses when the
        // customer has any, otherwise their own billing addresses as a fallback.
        $shippingAddressType = $user->addresses()->shipping()->exists() ? 'shipping' : 'billing';

        $rules = [
            'shipping_address_id' => [
                'required_without:shipping_first_name',
                Rule::exists('addresses', 'id')->where(
                    fn ($query) => $query->where('user_id', $user->id)->where('address_type', $shippingAddressType)
                ),
            ],
            'shipping_first_name' => 'required_without:shipping_address_id|string|max:255',
            'shipping_last_name' => 'required_without:shipping_address_id|string|max:255',
            'shipping_phone' => 'required_without:shipping_address_id|string|max:20',
            'shipping_address_line1' => 'required_without:shipping_address_id|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required_without:shipping_address_id|string|max:255',
            'shipping_state' => 'required_without:shipping_address_id|string|max:255',
            'shipping_zip' => 'required_without:shipping_address_id|string|max:20',
            'shipping_country' => 'required_without:shipping_address_id|string|max:2',
            'billing_same' => 'sometimes|boolean',
            'billing_address_id' => ['nullable', 'integer'],
            'payment_method' => PaymentGateway::paymentMethodRule(),
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
        ];

        if (! $billingSame) {
            $rules['billing_address_id'][] = Rule::exists('addresses', 'id')->where(
                fn ($query) => $query->where('user_id', $user->id)->where('address_type', 'billing')
            );
        }

        if (! $billingSame && ! $hasSavedBillingAddress) {
            $rules = array_merge($rules, [
                'billing_first_name' => 'required|string|max:255',
                'billing_last_name' => 'required|string|max:255',
                'billing_phone' => 'required|string|max:30',
                'billing_street' => 'required|string|max:255',
                'billing_apt' => 'nullable|string|max:255',
                'billing_city' => 'required|string|max:255',
                'billing_state' => 'required|string|max:255',
                'billing_zip' => 'required|string|max:20',
                'billing_country' => 'required|string|size:2|in:US',
            ]);
        }

        $validated = $request->validate($rules);

        // Everything below runs inside the transaction. The cart is read and
        // locked there, so a second checkout that waits for the same cart row
        // sees the cart the first request consumed and cannot create a
        // duplicate order from it.
        $order = DB::transaction(function () use ($user, $request, $billingSame, $validated, $shippingAddressType) {
            // Pessimistic lock to prevent double-order race condition. Taken
            // inside the transaction so it is held until the order commits.
            //
            // The parent cart row is locked first, then the cart item rows by
            // ascending id. This is the same protocol the cart endpoints use,
            // so a cart mutation and a checkout serialise on the same rows and
            // cannot observe each other's half finished work. The order, the
            // totals and the stock movement are all derived from these locked
            // rows.
            $cart = Cart::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

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

            $shippingAddress = null;
            if ($request->filled('shipping_address_id')) {
                $shippingAddress = $user->addresses()
                    ->where('address_type', $shippingAddressType)
                    ->findOrFail($validated['shipping_address_id']);
            }

            $billingAddress = null;
            if (! $billingSame && $request->filled('billing_address_id')) {
                $billingAddress = $user->addresses()
                    ->billing()
                    ->findOrFail($validated['billing_address_id']);
            }

            if (! $shippingAddress) {
                $shippingAddress = $user->addresses()->create([
                    'address_type' => 'shipping',
                    'first_name' => $validated['shipping_first_name'],
                    'last_name' => $validated['shipping_last_name'],
                    'phone' => $validated['shipping_phone'],
                    'address_line1' => $validated['shipping_address_line1'],
                    'address_line2' => $validated['shipping_address_line2'] ?? null,
                    'city' => $validated['shipping_city'],
                    'state' => $validated['shipping_state'],
                    'zip' => $validated['shipping_zip'],
                    'country' => $validated['shipping_country'],
                ]);
            }

            if ($billingSame) {
                $billingAddress = $shippingAddress;
            } elseif (! $billingAddress) {
                $billingAddress = $user->addresses()->create([
                    'address_type' => 'billing',
                    'first_name' => $validated['billing_first_name'],
                    'last_name' => $validated['billing_last_name'],
                    'phone' => $validated['billing_phone'],
                    'address_line1' => $validated['billing_street'],
                    'address_line2' => $validated['billing_apt'] ?? null,
                    'city' => $validated['billing_city'],
                    'state' => $validated['billing_state'],
                    'zip' => $validated['billing_zip'],
                    'country' => $validated['billing_country'],
                ]);
            }

            $subtotal = $cart->total;

            // Calculate shipping per vendor (multivendor)
            $shippingService = app(ShippingService::class);
            $shippingResult = $shippingService->calculate($cart, $shippingAddress);
            $shippingCost = $shippingResult['total'];

            // Calculate tax based on shipping address state
            $taxService = app(TaxService::class);
            $taxResult = $taxService->calculate($subtotal, $shippingAddress->state);
            $tax = $taxResult['amount'];

            $discount = 0;

            // Validate coupon early to include discount in initial total
            $coupon = null;
            if ($request->filled('coupon_code')) {
                $couponService = app(CouponService::class);
                $coupon = $couponService->validate($request->coupon_code, $user, $subtotal);
                $discount = $couponService->calculateDiscount($coupon, $subtotal);
            }

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
                    'supplier_id' => $product->supplierProduct?->supplier_id ?? $product->inventory?->supplier_id,
                    'supplier_product_id' => $product->supplier_product_id,
                    'unit_cost' => $product->supplier_product_id ? $product->sourcing_price : null,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->unit_price * $item->quantity,
                ]);
            }

            if ($coupon) {
                app(CouponService::class)->apply($coupon, $order, $user);
            }

            // Create commissions exactly once (idempotent)
            app(CommissionService::class)->createCommissions($order);

            // DO NOT credit seller balances here — moved to post-payment confirmation
            // Seller credit now happens in success() or webhook() after Stripe confirms payment

            // Decrement inventory to prevent overselling
            // (sourced items reserve supplier source stock instead)
            app(InventoryService::class)->reserveAndDecrementForCart($cart);

            $cart->items()->delete();

            // Cash on delivery is completed inside the transaction, so a
            // failure while opening the procurement order or the cash
            // collection rolls the order, the stock movement and the cart
            // consumption back together.
            if ($request->payment_method === 'cod') {
                app(CheckoutService::class)->finaliseCodOrder($order);
            }

            return $order;
        }, CheckoutService::TRANSACTION_ATTEMPTS);

        if ($order === null) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // External side effects only run once the transaction has committed.
        // They are isolated from the checkout: the order is already saved, so
        // a failing notification or audit job must not fail the request.
        app(CheckoutService::class)->dispatchPostCommitEffects($order);

        if ($request->payment_method === 'stripe') {
            return redirect()->route('checkout.stripe', $order);
        }

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

        // The card gateway needs a full Stripe configuration. Without one the
        // SDK call would fail with a 500, so refuse before touching Stripe.
        if (! PaymentGateway::stripeConfigured()) {
            return redirect()->route('orders.show', $order)
                ->with('error', PaymentGateway::unavailableMessage());
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
            'success_url' => route('checkout.success', $order).'?session_id={CHECKOUT_SESSION_ID}',
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
                        if (! $locked) {
                            return;
                        }
                        $locked->update([
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                            'confirmed_at' => now(),
                            'stripe_payment_intent_id' => $session->payment_intent,
                            'stripe_session_id' => $session->id,
                        ]);

                        // Credit seller balances NOW — payment confirmed
                        $this->creditSellers($locked);

                        // Create procurement orders for sourced items
                        app(ProcurementService::class)->createForOrder($locked);
                    });
                }
            } catch (\Exception $e) {
                Log::error('Stripe verification failed: '.$e->getMessage());
            }
        }

        // The page reflects the persisted order state only - never the
        // session_id query parameter or anything else the request supplied.
        $order->refresh();

        // A cancelled or refunded order is never a payment confirmation.
        if ($order->status === 'cancelled' || $order->payment_status === 'refunded') {
            return redirect()->route('orders.show', $order)->with('error', $order->payment_status === 'refunded'
                ? 'This order has been refunded, so there is no payment to confirm.'
                : 'This order was cancelled, so there is no payment to confirm.');
        }

        // Cash on delivery orders are placed but not settled: they get an
        // order-placement confirmation, never a payment-success claim.
        $paymentConfirmed = $order->payment_status === 'paid';

        return view('checkout.confirmation', compact('order', 'paymentConfirmed'));
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

        // Restore inventory, release reservations, reverse unpaid commissions
        app(OrderCancellationService::class)->cancel($order, 'Payment cancelled', null, auth()->id());

        return redirect()->route('cart.index')->with('error', 'Payment was cancelled. Your cart items are still saved.');
    }

    public function webhook(Request $request): Response
    {
        StripeGateway::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook verification failed: '.$e->getMessage());

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

                        // Create procurement orders for sourced items
                        app(ProcurementService::class)->createForOrder($order);
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
                // Sourced items: credit the seller's margin
                // (retail - platform commission - sourcing cost). Imported/custom
                // items keep the existing net-of-commission behaviour.
                $net = $item->supplier_product_id
                    ? round((float) $item->subtotal - $commissionService->getCommissionForItem($item)->amount - (float) ($item->unit_cost ?? 0) * $item->quantity, 2)
                    : $commissionService->netForItem($item);

                $payoutService->credit($item->seller_id, $net);
            }
        }
    }

}
