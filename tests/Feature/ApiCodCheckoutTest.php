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
use App\Models\SellerProfile;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCodCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function seller(float $rate = 10.0): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => $rate,
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
            'first_name'    => 'Jane',
            'last_name'     => 'Doe',
            'phone'         => '1234567890',
            'address_line1' => '456 Oak Ave',
            'city'          => 'New York',
            'state'         => 'NY',
            'zip'           => '10001',
            'country'       => 'US',
        ]);
    }

    private function cartFor(User $customer, Product $product, int $quantity = 1): Address
    {
        $address = $this->addressFor($customer);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $address;
    }

    public function test_api_cod_checkout_creates_collection_and_commission(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        $address = $this->cartFor($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $order = Order::firstOrFail();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);

        $this->assertSame(1, Commission::count());
        $this->assertEquals(10.00, (float) Commission::firstOrFail()->amount);

        $collection = CodCollection::firstOrFail();
        $this->assertSame('pending', $collection->status);
        $this->assertSame('seller', $collection->collector_role);
        $this->assertSame($seller->id, $collection->collector_id);
        $this->assertEquals(round((float) $order->total, 2), (float) $collection->expected_amount);

        $this->assertSame(0, Cart::count());
    }

    public function test_api_checkout_rejects_unknown_payment_method(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 10.00, 'status' => 'published']);
        $address = $this->cartFor($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'bitcoin',
        ])->assertStatus(422)->assertJsonValidationErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_api_checkout_requires_both_address_ids(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 10.00, 'status' => 'published']);
        $address = $this->cartFor($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
        ])->assertStatus(422)->assertJsonValidationErrors('billing_address_id');

        $this->assertSame(0, Order::count());
    }

    public function test_api_checkout_rejects_another_users_address(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 10.00, 'status' => 'published']);
        $customerAddress = $this->cartFor($customer, $product, 1);

        $otherUser = $this->customer();
        $otherAddress = $this->addressFor($otherUser);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $otherAddress->id,
            'billing_address_id'  => $customerAddress->id,
            'payment_method'      => 'cod',
        ])->assertNotFound();

        $this->assertSame(0, Order::count());
    }

    public function test_api_checkout_decrements_local_inventory_by_the_ordered_quantity(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $address = $this->cartFor($customer, $product, 3);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $this->assertSame(7, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(3, OrderItem::firstOrFail()->quantity);
    }

    public function test_api_checkout_reserves_supplier_source_stock_for_sourced_products(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = $this->seller();
        $customer = $this->customer();

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
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
            'supplier_product_id' => $supplierProduct->id,
            'sourcing_price'      => 40.00,
            'price'               => 79.99,
            'status'              => 'published',
        ]);

        $address = $this->cartFor($customer, $product, 3);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $stock = SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail();
        $this->assertSame(3, $stock->reserved_quantity);
        $this->assertSame(10, $stock->stock_quantity);
    }

    public function test_api_checkout_rejects_insufficient_stock_without_persisting_anything(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 1,
        ]);
        $address = $this->cartFor($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $this->assertSame(1, Order::count());
        $order = Order::firstOrFail();
        $this->assertSame(1, OrderItem::where('order_id', $order->id)->where('status', 'awaiting_stock')->count());
        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(1, $item->held_quantity);
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_api_checkout_rejects_insufficient_source_stock(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = $this->seller();
        $customer = $this->customer();

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'wholesale_price' => 40.00,
            'status'          => 'published',
        ]);

        SupplierProductStock::factory()->create([
            'supplier_product_id' => $supplierProduct->id,
            'stock_quantity'      => 1,
            'reserved_quantity'   => 0,
            'sold_quantity'       => 0,
        ]);

        $product = Product::factory()->create([
            'seller_id'           => $seller->id,
            'supplier_product_id' => $supplierProduct->id,
            'sourcing_price'      => 40.00,
            'price'               => 79.99,
            'status'              => 'published',
        ]);

        $address = $this->cartFor($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $this->assertSame(1, Order::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(
            1,
            SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail()->reserved_quantity
        );
    }

    public function test_api_checkout_rejects_unpublished_products(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'draft',
        ]);
        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $address = $this->cartFor($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertStatus(422)->assertJsonValidationErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_failed_api_checkout_preserves_the_cart(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 1,
        ]);
        $address = $this->cartFor($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $this->assertSame(0, Cart::count());
        $this->assertSame(0, CartItem::count());
        $this->assertSame(1, Order::count());
    }

    public function test_repeated_api_checkout_does_not_duplicate_commissions_or_reservations(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = $this->seller();
        $customer = $this->customer();

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
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
            'supplier_product_id' => $supplierProduct->id,
            'sourcing_price'      => 40.00,
            'price'               => 79.99,
            'status'              => 'published',
        ]);

        $address = $this->cartFor($customer, $product, 2);

        Sanctum::actingAs($customer);

        $payload = [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ];

        $this->postJson('/api/v1/checkout', $payload)->assertCreated();

        // The cart is empty now, so a repeat call cannot order again.
        $this->postJson('/api/v1/checkout', $payload)->assertStatus(422);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, Commission::count());
        $this->assertSame(
            2,
            SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail()->reserved_quantity
        );
    }
}
