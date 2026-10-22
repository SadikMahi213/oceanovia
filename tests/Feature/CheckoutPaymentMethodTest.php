<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrder;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CodCollection;
use App\Models\Commission;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProcurementOrder;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use App\Support\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Cash on delivery is the default payment method and the card gateway is
 * only offered while it is fully configured. These tests set the Stripe
 * configuration explicitly (and restore it) so they never depend on the
 * credentials that happen to exist in the environment.
 */
class CheckoutPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private ?array $originalStripeConfig = null;

    protected function tearDown(): void
    {
        if ($this->originalStripeConfig !== null) {
            config([
                'services.stripe.key' => $this->originalStripeConfig['key'],
                'services.stripe.secret' => $this->originalStripeConfig['secret'],
            ]);
        }

        parent::tearDown();
    }

    /**
     * Explicitly configure (or blank out) the card gateway credentials.
     */
    private function withStripeConfigured(bool $configured): void
    {
        if ($this->originalStripeConfig === null) {
            $this->originalStripeConfig = [
                'key' => config('services.stripe.key'),
                'secret' => config('services.stripe.secret'),
            ];
        }

        config([
            'services.stripe.key' => $configured ? 'pk_test_placeholder' : null,
            'services.stripe.secret' => $configured ? 'sk_test_placeholder' : null,
        ]);
    }

    private function checkoutContext(): array
    {
        $user = User::factory()->create(['role_type' => 'customer']);
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status'    => 'published',
            'price'     => 25.00,
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        return [$user, $product];
    }

    private function createAddress(User $user, string $type = 'shipping'): Address
    {
        return Address::create([
            'user_id'       => $user->id,
            'address_type'  => $type,
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'phone'         => '1234567890',
            'address_line1' => '123 Main St',
            'address_line2' => 'Apt 4B',
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);
    }

    public function test_checkout_page_defaults_to_cash_on_delivery(): void
    {
        $this->withStripeConfigured(false);
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('paymentMethod: "cod"', $html);
        $this->assertStringNotContainsString('paymentMethod: "stripe"', $html);
    }

    public function test_checkout_page_keeps_a_previous_selection_while_it_is_still_available(): void
    {
        $this->withStripeConfigured(true);
        [$user] = $this->checkoutContext();

        $html = $this->withSession(['_old_input' => ['payment_method' => 'stripe']])
            ->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('paymentMethod: "stripe"', $html);
    }

    public function test_checkout_page_drops_a_previous_selection_that_is_no_longer_available(): void
    {
        $this->withStripeConfigured(false);
        [$user] = $this->checkoutContext();

        $html = $this->withSession(['_old_input' => ['payment_method' => 'stripe']])
            ->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('paymentMethod: "cod"', $html);
        $this->assertStringNotContainsString('paymentMethod: "stripe"', $html);
    }

    public function test_stripe_option_is_not_offered_when_it_is_not_configured(): void
    {
        $this->withStripeConfigured(false);
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('value="stripe"', $html);
        $this->assertStringNotContainsString('js.stripe.com', $html);
        $this->assertStringContainsString('value="cod"', $html);
    }

    public function test_stripe_option_is_offered_when_it_is_configured(): void
    {
        $this->withStripeConfigured(true);
        [$user] = $this->checkoutContext();

        $html = $this->actingAs($user)->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('value="stripe"', $html);
        $this->assertStringContainsString('value="cod"', $html);
    }

    public function test_forged_stripe_submission_is_rejected_when_stripe_is_not_configured(): void
    {
        $this->withStripeConfigured(false);
        [$user, $product] = $this->checkoutContext();
        $address = $this->createAddress($user);

        $response = $this->actingAs($user)->from(route('checkout.index'))->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
            'billing_same'        => '1',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $response->assertSessionDoesntHaveErrors(['cart']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, ProcurementOrder::count());

        // No stock movement and the cart is untouched.
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(1, CartItem::count());

        // The message is customer safe: no config values or exception details.
        $message = session('errors')->first('payment_method');
        $this->assertStringContainsString('Cash on Delivery', $message);
        $this->assertStringNotContainsString('sk_', $message);
        $this->assertStringNotContainsString('pk_', $message);
    }

    public function test_forged_stripe_submission_never_falls_back_to_cash_on_delivery(): void
    {
        $this->withStripeConfigured(false);
        [$user, $product] = $this->checkoutContext();
        $address = $this->createAddress($user);

        $this->actingAs($user)->from(route('checkout.index'))->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
            'billing_same'        => '1',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count(), 'A rejected card submission must not create an order.');
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_api_forged_stripe_submission_is_rejected_when_stripe_is_not_configured(): void
    {
        $this->withStripeConfigured(false);
        [$user, $product] = $this->checkoutContext();
        $address = $this->createAddress($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'stripe',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('payment_method');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(1, CartItem::count());
    }

    public function test_cod_checkout_still_creates_the_full_order_and_lifecycle(): void
    {
        $this->withStripeConfigured(false);
        Queue::fake();
        [$user, $product] = $this->checkoutContext();
        $address = $this->createAddress($user);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(1, OrderItem::count());
        $this->assertSame($product->id, OrderItem::firstOrFail()->product_id);
        $this->assertSame(1, OrderItem::firstOrFail()->quantity);

        // Inventory deducted, cart cleared.
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(0, CartItem::count());

        // Commission and a pending cash collection. Procurement orders are only
        // created for supplier-sourced lines, so a local line creates none.
        $this->assertSame(1, Commission::count());
        $this->assertSame('pending', Commission::firstOrFail()->status);
        $this->assertSame(0, ProcurementOrder::count());
        $this->assertSame(1, CodCollection::count());
        $this->assertSame('pending', CodCollection::firstOrFail()->status);

        // The existing order processing job is still dispatched.
        Queue::assertPushed(ProcessOrder::class);
    }

    public function test_cod_checkout_of_a_supplier_sourced_line_still_creates_procurement_and_reserves_stock(): void
    {
        $this->withStripeConfigured(false);
        Queue::fake();

        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);
        $user = User::factory()->create(['role_type' => 'customer']);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => 40.00,
            'status'          => 'published',
        ]);

        SupplierProductStock::factory()->create([
            'supplier_product_id' => $supplierProduct->id,
            'stock_quantity'      => 10,
            'reserved_quantity'   => 0,
            'sold_quantity'       => 0,
        ]);

        $product = Product::factory()->create([
            'seller_id'           => $seller->id,
            'category_id'         => $category->id,
            'supplier_product_id' => $supplierProduct->id,
            'sourcing_price'      => 40.00,
            'price'               => 79.99,
            'status'              => 'published',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'unit_price' => $product->price,
        ]);

        $address = $this->createAddress($user);

        $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);

        // The source stock is reserved, not decremented, and a procurement
        // order is opened for the supplier.
        $stock = SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail();
        $this->assertSame(2, $stock->reserved_quantity);
        $this->assertSame(10, $stock->stock_quantity);
        $this->assertSame(1, ProcurementOrder::count());
        $this->assertSame('pending', ProcurementOrder::firstOrFail()->status);

        $this->assertSame(1, Commission::count());
        $this->assertSame(1, CodCollection::count());
        $this->assertSame('pending', CodCollection::firstOrFail()->status);
        $this->assertSame(0, CartItem::count());

        Queue::assertPushed(ProcessOrder::class);
    }

    public function test_cod_checkout_is_unaffected_when_the_card_gateway_is_configured(): void
    {
        $this->withStripeConfigured(true);
        Queue::fake();
        [$user, $product] = $this->checkoutContext();
        $address = $this->createAddress($user);

        $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(1, CodCollection::count());
        Queue::assertPushed(ProcessOrder::class);
    }

    public function test_unknown_payment_method_is_still_rejected(): void
    {
        $this->withStripeConfigured(false);
        [$user] = $this->checkoutContext();
        $address = $this->createAddress($user);

        $this->actingAs($user)->from(route('checkout.index'))->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'bitcoin',
            'billing_same'        => '1',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_payment_gateway_helper_reports_availability(): void
    {
        $this->withStripeConfigured(false);
        $this->assertFalse(PaymentGateway::stripeConfigured());
        $this->assertSame(['cod'], PaymentGateway::availableMethods());
        $this->assertFalse(PaymentGateway::isAvailable('stripe'));
        $this->assertTrue(PaymentGateway::isAvailable('cod'));
        $this->assertSame('cod', PaymentGateway::resolveSelection('stripe'));
        $this->assertSame('cod', PaymentGateway::resolveSelection(null));
        $this->assertSame('cod', PaymentGateway::resolveSelection('cod'));

        $this->withStripeConfigured(true);
        $this->assertTrue(PaymentGateway::stripeConfigured());
        $this->assertSame(['cod', 'stripe'], PaymentGateway::availableMethods());
        $this->assertTrue(PaymentGateway::isAvailable('stripe'));
        $this->assertSame('stripe', PaymentGateway::resolveSelection('stripe'));

        // A blank value counts as unconfigured.
        config(['services.stripe.secret' => '   ']);
        $this->assertFalse(PaymentGateway::stripeConfigured());
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Stripe route guard
    |--------------------------------------------------------------------------
    */

    /**
     * An order that was created while the card gateway was reachable can
     * still be opened at the legacy Stripe URL. Without a configuration the
     * SDK call would fail with a 500, so the route has to refuse first.
     */
    private function legacyStripeOrder(User $user, string $status = 'pending', string $paymentStatus = 'pending'): Order
    {
        $product = Product::factory()->create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'status'    => 'published',
            'price'     => 25.00,
        ]);

        $order = Order::create([
            'user_id'        => $user->id,
            'subtotal'       => 25.00,
            'total'          => 25.00,
            'status'         => $status,
            'payment_method' => 'stripe',
            'payment_status' => $paymentStatus,
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $product->seller_id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => 25.00,
            'subtotal'     => 25.00,
            'status'       => $status,
        ]);

        return $order->fresh('items');
    }

    public function test_legacy_stripe_route_refuses_when_the_gateway_is_unavailable(): void
    {
        $this->withStripeConfigured(false);
        $user = User::factory()->create(['role_type' => 'customer']);
        $order = $this->legacyStripeOrder($user);

        $response = $this->actingAs($user)->get(route('checkout.stripe', $order));

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('error');
        $this->assertSame(
            PaymentGateway::unavailableMessage(),
            session('error')
        );

        // Nothing was attempted and nothing was written.
        $order->refresh();
        $this->assertNull($order->stripe_session_id);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
    }

    public function test_legacy_stripe_route_still_refuses_a_cancelled_order_when_unavailable(): void
    {
        $this->withStripeConfigured(false);
        $user = User::factory()->create(['role_type' => 'customer']);
        $order = $this->legacyStripeOrder($user, 'cancelled');

        $response = $this->actingAs($user)->get(route('checkout.stripe', $order));

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(
            'This order has been cancelled and can no longer be paid.',
            session('error'),
            'The existing cancellation guard must keep priority over the availability guard.'
        );
    }

    public function test_legacy_stripe_route_still_redirects_a_paid_order_when_unavailable(): void
    {
        $this->withStripeConfigured(false);
        $user = User::factory()->create(['role_type' => 'customer']);
        $order = $this->legacyStripeOrder($user, 'confirmed', 'paid');

        $response = $this->actingAs($user)->get(route('checkout.stripe', $order));

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertNull(session('error'));
    }

    public function test_legacy_stripe_route_still_requires_order_ownership(): void
    {
        $this->withStripeConfigured(false);
        $owner = User::factory()->create(['role_type' => 'customer']);
        $intruder = User::factory()->create(['role_type' => 'customer']);
        $order = $this->legacyStripeOrder($owner);

        $this->actingAs($intruder)
            ->get(route('checkout.stripe', $order))
            ->assertForbidden();
    }

    public function test_legacy_stripe_route_creates_a_session_when_the_gateway_is_configured(): void
    {
        $this->withStripeConfigured(true);
        $user = User::factory()->create(['role_type' => 'customer']);
        $order = $this->legacyStripeOrder($user);

        // A fake Stripe HTTP client keeps the test offline: the SDK receives a
        // canned session response instead of calling the Stripe API.
        $fake = new class implements \Stripe\HttpClient\ClientInterface
        {
            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                return [
                    json_encode([
                        'id'             => 'cs_test_123',
                        'object'         => 'checkout.session',
                        'url'            => 'https://checkout.stripe.com/test/cs_test_123',
                        'status'         => 'open',
                        'payment_status' => 'unpaid',
                    ]),
                    200,
                    ['request-id' => 'req_test_123'],
                ];
            }
        };

        \Stripe\ApiRequestor::setHttpClient($fake);

        try {
            $response = $this->actingAs($user)->get(route('checkout.stripe', $order));

            $response->assertRedirect('https://checkout.stripe.com/test/cs_test_123');
            $this->assertNull(session('error'));

            $order->refresh();
            $this->assertSame('cs_test_123', $order->stripe_session_id);
            $this->assertSame('pending', $order->payment_status);
        } finally {
            \Stripe\ApiRequestor::setHttpClient(\Stripe\HttpClient\CurlClient::instance());
        }
    }
}
