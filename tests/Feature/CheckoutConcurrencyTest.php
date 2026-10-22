<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CodCollection;
use App\Models\Commission;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProcurementOrder;
use App\Models\User;
use App\Services\CodCollectionService;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * F-06 - checkout transaction integrity.
 *
 * The cart is read and locked inside the checkout transaction, the totals
 * come from that authoritative read, and the order, its items, the stock
 * movement, the cart consumption, the procurement order and the cash
 * collection are all committed together.
 */
class CheckoutConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        \App\Models\SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

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
            'address_line2' => 'Apt 4B',
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);
    }

    private function productWithStock(User $seller, int $stock = 10, float $price = 25.00): Product
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $price,
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => $stock,
        ]);

        return $product;
    }

    private function fillCart(User $customer, Product $product, int $quantity = 1): void
    {
        $cart = Cart::create(['user_id' => $customer->id]);

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transactional and sequential behaviour
    |--------------------------------------------------------------------------
    */

    public function test_empty_cart_is_rejected_without_creating_an_order(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        // A cart row that exists but holds no items.
        Cart::create(['user_id' => $customer->id]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_two_sequential_checkouts_of_the_same_cart_cannot_create_duplicate_orders(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        // The cart is consumed, so a replay of the same request has nothing
        // left to order.
        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_insufficient_stock_leaves_no_partial_state(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 1);

        $this->fillCart($customer, $product, 2);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, ProcurementOrder::count());
        $this->assertSame(1, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(1, CartItem::count());
    }

    public function test_failure_during_procurement_creation_rolls_back_the_checkout(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $this->mock(ProcurementService::class)
            ->shouldReceive('createForOrder')
            ->once()
            ->andThrow(new RuntimeException('procurement unavailable'));

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($customer)->post(route('checkout.store'), [
                'shipping_address_id' => $address->id,
                'payment_method'      => 'cod',
                'billing_same'        => '1',
            ]);
            $this->fail('The procurement failure must abort the checkout.');
        } catch (RuntimeException $e) {
            $this->assertSame('procurement unavailable', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, ProcurementOrder::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(1, CartItem::count());
    }

    public function test_failure_during_cod_collection_creation_rolls_back_the_checkout(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $this->mock(CodCollectionService::class)
            ->shouldReceive('createForOrder')
            ->once()
            ->andThrow(new RuntimeException('collection unavailable'));

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($customer)->post(route('checkout.store'), [
                'shipping_address_id' => $address->id,
                'payment_method'      => 'cod',
                'billing_same'        => '1',
            ]);
            $this->fail('The COD collection failure must abort the checkout.');
        } catch (RuntimeException $e) {
            $this->assertSame('collection unavailable', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, ProcurementOrder::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(1, CartItem::count());
    }

    public function test_the_order_is_built_from_the_cart_state_at_checkout_time(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        // The cart row is locked and re-read inside the transaction, so the
        // totals are taken from the cart as it exists when the order is
        // written - not from a snapshot captured earlier in the request.
        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();

        $this->assertSame(2, $item->quantity);
        $this->assertEquals(50.00, (float) $item->subtotal);
        $this->assertEquals(50.00, (float) $order->subtotal);
        $this->assertEquals(
            (float) $order->subtotal + (float) $order->shipping_cost + (float) $order->tax - (float) $order->discount,
            (float) $order->total
        );
    }

    public function test_a_cart_emptied_before_the_transaction_read_creates_no_order(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        // Emptied by a concurrent request before the checkout transaction
        // reads the cart.
        CartItem::query()->delete();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    /*
    |--------------------------------------------------------------------------
    | API checkout
    |--------------------------------------------------------------------------
    */

    public function test_api_checkout_of_an_empty_cart_creates_no_order(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertStatus(422)->assertJson(['message' => 'Cart is empty.']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
    }

    public function test_two_sequential_api_checkouts_of_the_same_cart_cannot_create_duplicate_orders(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        // The cart is consumed, so the replay has nothing left to order.
        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertStatus(422)->assertJson(['message' => 'Cart is empty.']);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_api_checkout_stock_insufficiency_leaves_no_partial_state(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 1);

        $this->fillCart($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertStatus(422)->assertJsonValidationErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, ProcurementOrder::count());
        $this->assertSame(1, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_api_checkout_persists_the_full_cod_lifecycle(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $order = Order::firstOrFail();

        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(1, Commission::count());
        $this->assertSame(1, CodCollection::count());
        $this->assertSame('pending', CodCollection::firstOrFail()->status);
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(0, CartItem::count());
    }
}
