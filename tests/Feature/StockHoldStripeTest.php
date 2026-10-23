<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stripe gating for stock-held orders.
 *
 * A zero-total / all-held order must never be sent to the gateway, and a
 * mixed order charges the confirmed-only total.
 */
class StockHoldStripeTest extends TestCase
{
    use RefreshDatabase;

    private ?array $originalStripeConfig = null;

    protected function tearDown(): void
    {
        if ($this->originalStripeConfig !== null) {
            config([
                'services.stripe.key'    => $this->originalStripeConfig['key'],
                'services.stripe.secret' => $this->originalStripeConfig['secret'],
            ]);
        }

        parent::tearDown();
    }

    private function withStripeConfigured(): void
    {
        $this->originalStripeConfig = [
            'key'    => config('services.stripe.key'),
            'secret' => config('services.stripe.secret'),
        ];

        config([
            'services.stripe.key'    => 'pk_test_placeholder',
            'services.stripe.secret' => 'sk_test_placeholder',
        ]);
    }

    private function seller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create(['user_id' => $seller->id, 'commission_rate' => 10.0, 'status' => 'approved']);

        return $seller;
    }

    private function customer(): User
    {
        return User::factory()->create(['role_type' => 'customer']);
    }

    private function addressFor(User $user): Address
    {
        return Address::create([
            'user_id'       => $user->id,
            'address_type'  => 'shipping',
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'phone'         => '1234567890',
            'address_line1' => '123 Main St',
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);
    }

    private function productWithStock(User $seller, int $stock, float $price = 25.00): Product
    {
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'published', 'price' => $price]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => $stock]);

        return $product;
    }

    private function fillCart(User $customer, Product $product, int $quantity): Cart
    {
        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $cart;
    }

    public function test_all_held_stripe_order_is_not_sent_to_the_gateway(): void
    {
        $this->withStripeConfigured();

        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0);

        $this->fillCart($customer, $product, 2);

        $response = $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
        ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertEquals(0.0, (float) $order->total);
        $this->assertNull($order->stripe_session_id);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_stripe_action_refuses_a_zero_total_order(): void
    {
        $this->withStripeConfigured();

        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->actingAs($customer)->get(route('checkout.stripe', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertNull($order->fresh()->stripe_session_id);
    }

    public function test_mixed_stripe_order_redirects_to_the_gateway(): void
    {
        $this->withStripeConfigured();

        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $inStock = $this->productWithStock($seller, 5, 30.00);
        $oos = $this->productWithStock($seller, 0, 50.00);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $inStock->id, 'quantity' => 1, 'unit_price' => $inStock->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $oos->id, 'quantity' => 2, 'unit_price' => $oos->price]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        // The payable amount is the confirmed portion only.
        $this->assertEquals(30.00, (float) $order->subtotal);

        // Offline fake Stripe HTTP client, matching the existing pattern.
        $fake = new class implements \Stripe\HttpClient\ClientInterface
        {
            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                return [
                    json_encode([
                        'id'             => 'cs_test_mixed',
                        'object'         => 'checkout.session',
                        'url'            => 'https://checkout.stripe.com/test/cs_test_mixed',
                        'status'         => 'open',
                        'payment_status' => 'unpaid',
                    ]),
                    200,
                    ['request-id' => 'req_test_mixed'],
                ];
            }
        };
        \Stripe\ApiRequestor::setHttpClient($fake);

        try {
            $this->actingAs($customer)->get(route('checkout.stripe', $order))
                ->assertRedirect('https://checkout.stripe.com/test/cs_test_mixed');

            $this->assertSame('cs_test_mixed', $order->fresh()->stripe_session_id);
        } finally {
            \Stripe\ApiRequestor::setHttpClient(\Stripe\HttpClient\CurlClient::instance());
        }
    }

    public function test_confirming_held_item_on_a_paid_order_updates_totals_without_a_second_charge(): void
    {
        $this->withStripeConfigured();

        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $inStock = $this->productWithStock($seller, 5, 30.00);
        $oos = $this->productWithStock($seller, 0, 50.00);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $inStock->id, 'quantity' => 1, 'unit_price' => $inStock->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $oos->id, 'quantity' => 2, 'unit_price' => $oos->price]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $heldItem = OrderItem::where('status', 'awaiting_stock')->firstOrFail();

        // The customer already paid the confirmed portion through Stripe.
        $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);

        // Seller restocks and confirms.
        Inventory::where('product_id', $oos->id)->update(['stock_quantity' => 10]);

        $this->actingAs($seller)->post(route('seller.stock.confirm', [$order, $heldItem]))
            ->assertRedirect();

        $order->refresh();
        $heldItem->refresh();

        $this->assertSame('pending', $heldItem->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(130.00, (float) $order->subtotal); // 30 + 100
        // No new gateway hand-off happened: the session id is unchanged.
        $this->assertNull($order->stripe_session_id);
    }
}
