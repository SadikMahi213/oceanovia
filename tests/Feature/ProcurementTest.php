<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ProcurementOrder;
use App\Models\Product;
use App\Models\SupplierBalance;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use RefreshDatabase;

    private function supplier(): User
    {
        return User::factory()->create(['role_type' => 'supplier']);
    }

    private function seller(): User
    {
        return User::factory()->create(['role_type' => 'seller']);
    }

    private function category(): Category
    {
        return Category::factory()->create(['status' => true]);
    }

    private function addressFor(User $user): Address
    {
        return Address::create([
            'user_id'        => $user->id,
            'address_type'   => 'shipping',
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'phone'          => '1234567890',
            'address_line1'  => '456 Oak Ave',
            'city'           => 'New York',
            'state'          => 'NY',
            'zip'            => '10001',
            'country'        => 'US',
        ]);
    }

    /**
     * Build a supplier source product (with stock) and a seller listing
     * linked to it, mimicking the seller import flow.
     */
    private function sourcedListing(User $supplier, User $seller, Category $category, float $wholesale, float $retail, int $stockQty): array
    {
        $sp = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => $wholesale,
            'status'          => 'published',
        ]);

        SupplierProductStock::factory()->create([
            'supplier_product_id' => $sp->id,
            'stock_quantity'      => $stockQty,
            'reserved_quantity'   => 0,
            'sold_quantity'       => 0,
        ]);

        $product = Product::factory()->create([
            'seller_id'           => $seller->id,
            'category_id'         => $category->id,
            'supplier_product_id' => $sp->id,
            'sourcing_price'      => $wholesale,
            'price'               => $retail,
            'status'              => 'published',
        ]);

        return [$sp, $product];
    }

    private function checkoutCod(User $customer, Product $product, int $quantity, Address $address): \Illuminate\Testing\TestResponse
    {
        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
        ]);
    }

    public function test_seller_import_creates_linked_product_with_retail_price(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $category = $this->category();

        $sp = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => 40.00,
            'status'          => 'published',
        ]);
        SupplierProductStock::factory()->create(['supplier_product_id' => $sp->id, 'stock_quantity' => 50]);

        $response = $this->actingAs($seller)->post(route('seller.suppliers.import'), [
            'supplier_product_id' => $sp->id,
            'price'               => 79.99,
        ]);

        $response->assertRedirect(route('seller.suppliers.connected'));

        $this->assertDatabaseHas('products', [
            'seller_id'           => $seller->id,
            'supplier_product_id' => $sp->id,
            'price'               => 79.99,
            'sourcing_price'      => 40.00,
            'status'              => 'published',
        ]);
    }

    public function test_cod_order_creates_procurement_and_reserves_source_stock(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $category = $this->category();

        [$sp, $product] = $this->sourcedListing($supplier, $seller, $category, 40.00, 79.99, 10);

        $customer = User::factory()->create();
        $address = $this->addressFor($customer);

        $response = $this->checkoutCod($customer, $product, 3, $address);
        $response->assertRedirect();

        // Procurement order created for (order, supplier, seller).
        $procurement = ProcurementOrder::where('supplier_id', $supplier->id)
            ->where('seller_id', $seller->id)
            ->firstOrFail();
        $this->assertNotNull($procurement->order_id);
        $this->assertSame('pending', $procurement->status);
        $this->assertSame(3, $procurement->total_quantity);
        $this->assertEquals(120.00, (float) $procurement->subtotal);

        // Source stock reserved (not yet decremented).
        $stock = SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail();
        $this->assertSame(3, $stock->reserved_quantity);
        $this->assertSame(10, $stock->stock_quantity);

        // Order items carry sourcing linkage + cost.
        $this->assertDatabaseHas('order_items', [
            'order_id'            => $procurement->order_id,
            'supplier_id'         => $supplier->id,
            'supplier_product_id' => $sp->id,
            'unit_cost'           => 40.00,
            'quantity'            => 3,
        ]);
    }

    public function test_oversell_is_blocked_at_checkout(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $category = $this->category();

        // Only 1 unit available but the customer orders 2.
        [$sp, $product] = $this->sourcedListing($supplier, $seller, $category, 40.00, 79.99, 1);

        $customer = User::factory()->create();
        $address = $this->addressFor($customer);

        $response = $this->checkoutCod($customer, $product, 2, $address);

        $response->assertSessionHasErrors('cart');
        $this->assertSame(0, ProcurementOrder::count());
        $this->assertSame(0, \App\Models\Order::count());

        $stock = SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail();
        $this->assertSame(0, $stock->reserved_quantity);
        $this->assertSame(1, $stock->stock_quantity);
    }

    public function test_supplier_fulfill_syncs_procurement_status_and_tracking(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $category = $this->category();

        [$sp, $product] = $this->sourcedListing($supplier, $seller, $category, 40.00, 79.99, 10);

        $customer = User::factory()->create();
        $address = $this->addressFor($customer);
        $this->checkoutCod($customer, $product, 2, $address);

        $procurement = ProcurementOrder::firstOrFail();
        $order = $procurement->order()->firstOrFail();

        // Supplier walks the order through accept -> packed -> ready -> ship.
        $this->actingAs($supplier)->post(route('supplier.orders.accept', $order));
        $this->assertSame('processing', $procurement->fresh()->status);

        $this->actingAs($supplier)->post(route('supplier.orders.packed', $order));
        $this->assertSame('packed', $procurement->fresh()->status);

        $this->actingAs($supplier)->post(route('supplier.orders.ready', $order));
        $this->assertSame('ready_for_pickup', $procurement->fresh()->status);

        $this->actingAs($supplier)->patch(route('supplier.orders.fulfill', $order), [
            'carrier'         => 'UPS',
            'tracking_number' => '1Z999AA10123456784',
            'tracking_url'    => 'https://www.ups.com/track?tracknum=1Z999AA10123456784',
        ]);

        $procurement = $procurement->fresh();
        $this->assertSame('shipped', $procurement->status);
        $this->assertSame('UPS', $procurement->carrier);
        $this->assertSame('1Z999AA10123456784', $procurement->tracking_number);
        $this->assertNotNull($procurement->tracking_url);
        $this->assertNotNull($procurement->shipped_at);

        // Source stock committed on ship: reserved cleared, on-hand reduced, sold increased.
        $stock = SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail();
        $this->assertSame(0, $stock->reserved_quantity);
        $this->assertSame(8, $stock->stock_quantity);
        $this->assertSame(2, $stock->sold_quantity);
    }

    public function test_delivered_procurement_settles_supplier_balance_once(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $category = $this->category();

        [$sp, $product] = $this->sourcedListing($supplier, $seller, $category, 40.00, 79.99, 10);

        $customer = User::factory()->create();
        $address = $this->addressFor($customer);
        $this->checkoutCod($customer, $product, 3, $address);

        $procurement = ProcurementOrder::firstOrFail();
        $order = $procurement->order()->firstOrFail();

        // Supplier ships it.
        $this->actingAs($supplier)->post(route('supplier.orders.accept', $order));
        $this->actingAs($supplier)->patch(route('supplier.orders.fulfill', $order), [
            'tracking_number' => 'TRK123',
        ]);
        $this->assertSame('shipped', $procurement->fresh()->status);

        // Seller confirms delivery -> settlement triggered.
        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'delivered',
        ]);

        $procurement = $procurement->fresh();
        $this->assertSame('delivered', $procurement->status);
        $this->assertNotNull($procurement->settled_at);

        $expectedAmount = (float) $procurement->subtotal; // 120.00
        $balance = SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertEquals($expectedAmount, (float) $balance->balance);
        $this->assertEquals($expectedAmount, (float) $balance->total_earned);

        // Re-syncing must not settle again (idempotent).
        app(\App\Services\ProcurementService::class)->syncForOrder($order);

        $balance = $balance->fresh();
        $this->assertEquals($expectedAmount, (float) $balance->balance);

        $creditCount = Transaction::where('reference_type', ProcurementOrder::class)
            ->where('reference_id', $procurement->id)
            ->where('type', 'credit')
            ->count();
        $this->assertSame(1, $creditCount);
    }

    public function test_cancel_releases_reservation_and_cancels_procurement(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $category = $this->category();

        [$sp, $product] = $this->sourcedListing($supplier, $seller, $category, 40.00, 79.99, 10);

        $customer = User::factory()->create();
        $address = $this->addressFor($customer);
        $this->checkoutCod($customer, $product, 4, $address);

        $procurement = ProcurementOrder::firstOrFail();
        $order = $procurement->order()->firstOrFail();

        // Reservation held while order is open.
        $stock = SupplierProductStock::where('supplier_product_id', $sp->id)->firstOrFail();
        $this->assertSame(4, $stock->reserved_quantity);

        // Customer cancels the (unpaid) order.
        $response = $this->actingAs($customer)->get(route('checkout.cancel', $order));
        $response->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $procurement->fresh()->status);
        $this->assertNotNull($procurement->fresh()->cancelled_at);

        // Reservation released; physical stock untouched (never shipped).
        $stock = $stock->fresh();
        $this->assertSame(0, $stock->reserved_quantity);
        $this->assertSame(10, $stock->stock_quantity);
        $this->assertSame(0, $stock->sold_quantity);
    }
}