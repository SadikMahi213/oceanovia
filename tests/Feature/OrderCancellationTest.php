<?php

namespace Tests\Feature;

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
use App\Models\SellerProfile;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use App\Services\CodCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

        return $seller;
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

    private function checkoutCod(User $customer, Product $product, int $quantity, Address $address): void
    {
        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
        ])->assertRedirect();
    }

    public function test_customer_cancel_restores_local_inventory_and_reverses_commission(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $address = $this->addressFor($customer);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->checkoutCod($customer, $product, 3, $address);

        $order = Order::firstOrFail();
        $this->assertSame(7, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame('pending', Commission::firstOrFail()->status);

        $this->actingAs($customer)->post(route('customer.orders.cancel', $order), [
            'reason' => 'Changed my mind',
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame('reversed', Commission::firstOrFail()->status);
        $this->assertSame('cancelled', $order->items()->firstOrFail()->status);
    }

    public function test_customer_cancel_releases_sourced_reservation(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = $this->seller();
        $category = Category::factory()->create(['status' => true]);

        $sp = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => 40.00,
            'status'          => 'published',
        ]);
        SupplierProductStock::factory()->create([
            'supplier_product_id' => $sp->id,
            'stock_quantity'      => 10,
            'reserved_quantity'   => 0,
            'sold_quantity'       => 0,
        ]);

        $product = Product::factory()->create([
            'seller_id'           => $seller->id,
            'category_id'         => $category->id,
            'supplier_product_id' => $sp->id,
            'sourcing_price'      => 40.00,
            'price'               => 79.99,
            'status'              => 'published',
        ]);

        $customer = User::factory()->create(['role_type' => 'customer']);
        $address = $this->addressFor($customer);

        $this->checkoutCod($customer, $product, 2, $address);

        $this->assertSame(2, SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail()->reserved_quantity);

        $order = Order::firstOrFail();
        $this->actingAs($customer)->post(route('customer.orders.cancel', $order), ['reason' => 'Nope']);

        $this->assertSame(0, SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail()->reserved_quantity);
        $this->assertSame(10, SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail()->stock_quantity);
    }

    public function test_cancel_is_idempotent_and_does_not_double_restore(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $address = $this->addressFor($customer);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 5]);

        $this->checkoutCod($customer, $product, 2, $address);
        $order = Order::firstOrFail();

        app(\App\Services\OrderCancellationService::class)->cancel($order, 'first');
        app(\App\Services\OrderCancellationService::class)->cancel($order, 'second');

        $this->assertSame(5, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_cancel_is_blocked_after_cash_collected(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $address = $this->addressFor($customer);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);

        $this->checkoutCod($customer, $product, 1, $address);

        $order = Order::firstOrFail();
        $collection = CodCollection::firstOrFail();

        (new CodCollectionService())->recordCollection(
            $collection,
            (float) $collection->expected_amount,
            User::factory()->create(['role_type' => 'admin'])->id,
        );

        $this->actingAs($customer)->post(route('customer.orders.cancel', $order), ['reason' => 'too late'])
            ->assertSessionHasErrors('cancel');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('collected', $collection->fresh()->status);
    }

    public function test_supplier_reject_only_cancels_its_own_items(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $sourced = Product::factory()->create(['seller_id' => $seller->id, 'price' => 70.00]);
        $local = Product::factory()->create(['seller_id' => $seller->id, 'price' => 30.00]);

        $order = Order::create([
            'user_id'        => $customer->id,
            'subtotal'       => 100.00,
            'total'          => 100.00,
            'status'         => 'confirmed',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $supplierItem = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $sourced->id,
            'seller_id'    => $seller->id,
            'supplier_id'  => $supplier->id,
            'supplier_product_id' => null,
            'product_name' => 'Sourced',
            'quantity'     => 1,
            'unit_price'   => 70.00,
            'subtotal'     => 70.00,
            'status'       => 'processing',
        ]);

        $otherItem = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $local->id,
            'seller_id'    => $seller->id,
            'product_name' => 'Local',
            'quantity'     => 1,
            'unit_price'   => 30.00,
            'subtotal'     => 30.00,
            'status'       => 'processing',
        ]);

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), [
            'reason' => 'Out of stock',
        ])->assertRedirect();

        $this->assertSame('cancelled', $supplierItem->fresh()->status);
        $this->assertSame('processing', $otherItem->fresh()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_checkout_cancel_restores_local_inventory(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $address = $this->addressFor($customer);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 50.00,
            'status'    => 'published',
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 4]);

        $this->checkoutCod($customer, $product, 1, $address);
        $order = Order::firstOrFail();

        $this->assertSame(3, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        $this->actingAs($customer)->get(route('checkout.cancel', $order))->assertRedirect();

        $this->assertSame(4, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $order->items()->firstOrFail()->status);
    }
}

