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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API parity for stock-held orders.
 *
 * The API checkout endpoint must mirror the web flow: always create an
 * order, split items into confirmed vs awaiting_stock, charge the
 * confirmed-only total, and never return a payment_url for zero-total
 * orders.
 */
class ApiStockHoldWorkflowTest extends TestCase
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

    public function test_api_mixed_cart_returns_confirmed_total_and_held_items(): void
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

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'stripe',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $order = Order::firstOrFail();

        $this->assertEquals($order->id, $data['data']['id']);
        // Subtotal is confirmed items only (30.00)
        $this->assertEquals(30.00, (float) $data['data']['subtotal']);
        // Total includes tax + shipping on confirmed items.
        $this->assertEquals($order->total, (float) $data['data']['total']);

        // Payment URL points to the checkout page for the order (client redirects to Stripe).
        $this->assertNotNull($data['payment_url']);
        $this->assertStringContainsString('checkout', $data['payment_url']);

        // Held item present in response.
        $heldItem = collect($data['data']['items'])->firstWhere('status', 'awaiting_stock');
        $this->assertNotNull($heldItem);
        $this->assertEquals(2, $heldItem['quantity']);
        $this->assertEquals(2, $heldItem['held_quantity']);
    }

    public function test_api_all_held_zero_total_returns_no_payment_url(): void
    {
        $this->withStripeConfigured();

        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0);

        $this->fillCart($customer, $product, 2);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'stripe',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $order = Order::firstOrFail();

        $this->assertEquals(0.0, (float) $data['data']['total']);
        $this->assertArrayNotHasKey('payment_url', $data);
        $this->assertEquals('awaiting_stock', $data['data']['items'][0]['status']);
    }

    public function test_api_cod_with_held_items_creates_order_with_payment_url_to_order(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $inStock = $this->productWithStock($seller, 5, 30.00);
        $oos = $this->productWithStock($seller, 0, 50.00);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $inStock->id, 'quantity' => 1, 'unit_price' => $inStock->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $oos->id, 'quantity' => 2, 'unit_price' => $oos->price]);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $order = Order::firstOrFail();
        // COD response includes payment_url pointing to the order show page.
        $this->assertArrayHasKey('payment_url', $data);
        $this->assertStringContainsString('orders', $data['payment_url']);
        $this->assertEquals($order->total, (float) $data['data']['total']);
        $this->assertSame('pending', $data['data']['payment_status']);
    }
}