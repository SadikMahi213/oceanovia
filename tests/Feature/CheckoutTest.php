<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function checkoutContext(): array
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 25.00,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        return [$user, $product];
    }

    private function createAddress(User $user, string $type = 'shipping', array $overrides = []): Address
    {
        return Address::create(array_merge([
            'user_id' => $user->id,
            'address_type' => $type,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'address_line2' => 'Apt 4B',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'US',
        ], $overrides));
    }

    private function checkoutPayload(Address $shipping, array $overrides = []): array
    {
        return array_merge([
            'shipping_address_id' => $shipping->id,
            'payment_method' => 'cod',
            'billing_same' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_access_checkout(): void
    {
        $response = $this->get(route('checkout.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_auth_user_can_checkout_with_cod(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 49.99,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'address_type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'cod',
        ]);
    }

    public function test_order_created_with_correct_items(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 25.00,
            'name' => 'Order Item Test',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price,
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'address_type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '9876543210',
            'address_line1' => '456 Oak Ave',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_name' => 'ORDER ITEM TEST',
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
        ]);
    }

    public function test_billing_same_uses_shipping_address_without_creating_a_duplicate(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
            'billing_first_name' => 'Ignored',
            'billing_street' => 'Ignored Billing Street',
        ]));

        $response->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame($shipping->id, $order->shipping_address_id);
        $this->assertSame($shipping->id, $order->billing_address_id);
        $this->assertSame(1, Address::where('user_id', $user->id)->count());
    }

    public function test_saved_billing_address_remains_distinct_from_shipping(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);
        $billing = $this->createAddress($user, 'billing', [
            'first_name' => 'Billing',
            'address_line1' => '456 Billing St',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip' => '94105',
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
            'billing_same' => '0',
            'billing_address_id' => $billing->id,
        ]));

        $response->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame($shipping->id, $order->shipping_address_id);
        $this->assertSame($billing->id, $order->billing_address_id);
        $this->assertNotSame($order->shipping_address_id, $order->billing_address_id);
        $this->assertSame(2, Address::where('user_id', $user->id)->count());
    }

    public function test_new_billing_details_create_a_distinct_billing_address(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
            'billing_same' => '0',
            'billing_first_name' => 'Billing',
            'billing_last_name' => 'Person',
            'billing_phone' => '5551234567',
            'billing_street' => '789 Billing Ave',
            'billing_apt' => 'Suite 10',
            'billing_city' => 'Seattle',
            'billing_state' => 'WA',
            'billing_zip' => '98101',
            'billing_country' => 'US',
        ]));

        $response->assertRedirect();

        $order = Order::firstOrFail();
        $billing = Address::where('user_id', $user->id)
            ->where('address_type', 'billing')
            ->firstOrFail();

        $this->assertSame($shipping->id, $order->shipping_address_id);
        $this->assertSame($billing->id, $order->billing_address_id);
        $this->assertSame('Billing', $billing->first_name);
        $this->assertSame('Person', $billing->last_name);
        $this->assertSame('5551234567', $billing->phone);
        $this->assertSame('789 Billing Ave', $billing->address_line1);
        $this->assertSame('Suite 10', $billing->address_line2);
        $this->assertSame('Seattle', $billing->city);
        $this->assertSame('WA', $billing->state);
        $this->assertSame('98101', $billing->zip);
        $this->assertSame('US', $billing->country);
        $this->assertSame(2, Address::where('user_id', $user->id)->count());
    }

    public function test_missing_required_new_billing_fields_do_not_create_an_order(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
            'billing_same' => '0',
        ]));

        $response->assertSessionHasErrors([
            'billing_first_name',
            'billing_last_name',
            'billing_phone',
            'billing_street',
            'billing_city',
            'billing_state',
            'billing_zip',
            'billing_country',
        ]);

        $this->assertSame(0, Order::count());
        $this->assertSame(1, Address::where('user_id', $user->id)->count());
    }

    public function test_missing_new_billing_phone_or_country_is_rejected(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
            'billing_same' => '0',
            'billing_first_name' => 'Billing',
            'billing_last_name' => 'Person',
            'billing_street' => '789 Billing Ave',
            'billing_city' => 'Seattle',
            'billing_state' => 'WA',
            'billing_zip' => '98101',
        ]));

        $response->assertSessionHasErrors(['billing_phone', 'billing_country']);
        $this->assertSame(0, Order::count());
    }

    public function test_cross_user_shipping_and_billing_address_ids_are_rejected(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);

        $otherUser = User::factory()->create();
        $otherShipping = $this->createAddress($otherUser);
        $otherBilling = $this->createAddress($otherUser, 'billing');

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($otherShipping))
            ->assertSessionHasErrors('shipping_address_id');

        $this->assertSame(0, Order::count());

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
            'billing_same' => '0',
            'billing_address_id' => $otherBilling->id,
        ]))->assertSessionHasErrors('billing_address_id');

        $this->assertSame(0, Order::count());
    }

    public function test_billing_only_address_is_available_as_shipping_when_no_shipping_address_exists(): void
    {
        [$user] = $this->checkoutContext();
        $billing = $this->createAddress($user, 'billing');

        $response = $this->actingAs($user)->get(route('checkout.index'));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('name="shipping_address_id" value="'.$billing->id.'"', $html);
        $this->assertStringContainsString('No shipping address is saved yet', $html);
        $this->assertStringContainsString('Billing address', $html);

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($billing))
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame($billing->id, $order->shipping_address_id);
        $this->assertSame($billing->id, $order->billing_address_id);
        $this->assertSame(1, Address::where('user_id', $user->id)->count());
    }

    public function test_billing_addresses_are_not_shown_as_shipping_when_shipping_address_exists(): void
    {
        [$user] = $this->checkoutContext();
        $shipping = $this->createAddress($user);
        $billing = $this->createAddress($user, 'billing');

        $response = $this->actingAs($user)->get(route('checkout.index'));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('name="shipping_address_id" value="'.$shipping->id.'"', $html);
        $this->assertStringNotContainsString('name="shipping_address_id" value="'.$billing->id.'"', $html);
        $this->assertStringNotContainsString('No shipping address is saved yet', $html);

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($billing))
            ->assertSessionHasErrors('shipping_address_id');

        $this->assertSame(0, Order::count());
    }

    public function test_cross_user_billing_address_cannot_be_used_as_shipping_fallback(): void
    {
        [$user] = $this->checkoutContext();
        $this->createAddress($user, 'billing');

        $otherUser = User::factory()->create();
        $otherBilling = $this->createAddress($otherUser, 'billing');

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($otherBilling))
            ->assertSessionHasErrors('shipping_address_id');

        $this->assertSame(0, Order::count());
    }

    public function test_new_addresses_roll_back_when_order_transaction_fails(): void
    {
        [$user, $product] = $this->checkoutContext();

        Inventory::create([
            'product_id' => $product->id,
            'stock_quantity' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'payment_method' => 'cod',
            'billing_same' => '0',
            'shipping_first_name' => 'New',
            'shipping_last_name' => 'Shipping',
            'shipping_phone' => '5550001111',
            'shipping_address_line1' => '10 New Shipping St',
            'shipping_city' => 'Denver',
            'shipping_state' => 'CO',
            'shipping_zip' => '80202',
            'shipping_country' => 'US',
            'billing_first_name' => 'New',
            'billing_last_name' => 'Billing',
            'billing_phone' => '5550002222',
            'billing_street' => '20 New Billing St',
            'billing_city' => 'Denver',
            'billing_state' => 'CO',
            'billing_zip' => '80202',
            'billing_country' => 'US',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Order::count());
        $order = Order::firstOrFail();
        $this->assertSame(2, Address::where('user_id', $user->id)->count());
        $this->assertSame(0, CartItem::whereHas('cart', fn ($query) => $query->where('user_id', $user->id))->count());
        $this->assertSame(1, OrderItem::where('order_id', $order->id)->where('status', 'awaiting_stock')->count());
        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(1, $item->held_quantity);
        $this->assertSame(0.0, (float) $order->total);
    }

    public function test_checkout_form_has_one_shipping_phone_and_billing_controls(): void
    {
        [$user] = $this->checkoutContext();
        $this->createAddress($user, 'billing');

        $response = $this->actingAs($user)->get(route('checkout.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'name="shipping_phone"'));
        $this->assertStringContainsString('name="billing_same" value="0"', $html);
        $this->assertStringContainsString('name="billing_same" value="1"', $html);
        $this->assertStringContainsString('name="billing_address_id"', $html);
        $this->assertStringContainsString('name="billing_phone"', $html);
        $this->assertStringContainsString('name="billing_country"', $html);
        $this->assertStringContainsString(':disabled="!showNewShipping"', $html);
        $this->assertStringContainsString(':disabled="sameAsShipping || !!selectedBilling"', $html);
    }

    public function test_order_views_render_actual_address_columns(): void
    {
        $user = User::factory()->create();
        $shipping = $this->createAddress($user);
        $billing = $this->createAddress($user, 'billing', [
            'first_name' => 'Billing',
            'last_name' => 'Person',
            'address_line1' => '456 Billing St',
            'address_line2' => 'Suite 10',
            'city' => 'Seattle',
            'state' => 'WA',
            'zip' => '98101',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => 25.00,
            'shipping_cost' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => 25.00,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_address_id' => $shipping->id,
            'billing_address_id' => $billing->id,
        ]);

        $this->actingAs($user)->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('John Doe')
            ->assertSee('123 Main St')
            ->assertSee('Apt 4B')
            ->assertSee('456 Billing St');

        $this->actingAs($user)->get(route('customer.orders.invoice', $order))
            ->assertOk()
            ->assertSee('456 Billing St')
            ->assertSee('Suite 10');

        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('123 Main St')
            ->assertSee('456 Billing St');
    }

    /**
     * The card gateway is only offered while it is configured, so this
     * exercises the hand-off with the configuration explicitly present.
     * No Stripe API call happens in this request: the checkout only
     * redirects to the Stripe route, which creates the session.
     */
    public function test_stripe_checkout_creates_one_order_and_redirects_to_stripe_when_stripe_is_configured(): void
    {
        $originalKey = config('services.stripe.key');
        $originalSecret = config('services.stripe.secret');
        config([
            'services.stripe.key'    => 'pk_test_placeholder',
            'services.stripe.secret' => 'sk_test_placeholder',
        ]);

        try {
            [$user] = $this->checkoutContext();
            $shipping = $this->createAddress($user);

            $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shipping, [
                'payment_method' => 'stripe',
            ]));

            $order = Order::firstOrFail();

            $response->assertRedirect(route('checkout.stripe', $order));
            $this->assertSame(1, Order::count());
            $this->assertSame('stripe', $order->payment_method);
            $this->assertSame('pending', $order->status);
            $this->assertSame('pending', $order->payment_status);
            $this->assertSame(1, Address::where('user_id', $user->id)->count());
        } finally {
            config([
                'services.stripe.key'    => $originalKey,
                'services.stripe.secret' => $originalSecret,
            ]);
        }
    }
}
