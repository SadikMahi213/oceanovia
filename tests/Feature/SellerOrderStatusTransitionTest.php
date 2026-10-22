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
use App\Models\ProcurementOrder;
use App\Models\SupplierBalance;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A seller may only move their own order lines forward through the
 * legitimate fulfilment steps, and never out of a terminal state.
 *
 * Otherwise a seller can resurrect a cancelled order or declare an order
 * delivered before it was ever handed over. Both re-commit the supplier's
 * source stock and settle the supplier for goods that were never sold.
 */
class SellerOrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A cash on delivery order for a supplier-sourced listing.
     *
     * @return array{0: User, 1: User, 2: User, 3: Order, 4: SupplierProduct}
     */
    private function sourcedOrder(int $stock = 10): array
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $seller = User::factory()->create(['role_type' => 'seller']);
        $customer = User::factory()->create(['role_type' => 'customer']);
        $category = Category::factory()->create(['status' => true]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => 40.00,
            'status'          => 'published',
        ]);

        SupplierProductStock::factory()->create([
            'supplier_product_id' => $supplierProduct->id,
            'stock_quantity'      => $stock,
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

        $address = Address::create([
            'user_id'       => $customer->id,
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

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'unit_price' => $product->price,
        ]);

        $response = $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ]);

        $response->assertRedirect();

        $order = Order::firstOrFail();

        return [$supplier, $seller, $customer, $order->fresh('items'), $supplierProduct];
    }

    /**
     * The supplier's available balance (0 when nothing was ever credited).
     */
    private function supplierBalance(int $supplierId): float
    {
        $balance = SupplierBalance::where('supplier_id', $supplierId)->first();

        return $balance === null ? 0.0 : (float) $balance->balance;
    }

    public function test_seller_cannot_resurrect_a_cancelled_order(): void
    {
        [$supplier, $seller, $customer, $order, $supplierProduct] = $this->sourcedOrder();

        // The seller cancels the order: the source reservation is released and
        // the physical source stock is left untouched.
        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'cancelled',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->items()->firstOrFail()->status);

        $stock = SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail();
        $this->assertSame(10, $stock->stock_quantity);
        $this->assertSame(0, $stock->reserved_quantity);
        $this->assertSame(0, $stock->sold_quantity);

        // Re-opening the cancelled order must be refused.
        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'shipped',
        ])->assertRedirect()->assertSessionHas('error');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $order->items()->firstOrFail()->status);

        // The supplier's source stock must not be committed a second time and
        // the supplier must not be settled for the cancelled order.
        $stock = SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail();
        $this->assertSame(10, $stock->stock_quantity);
        $this->assertSame(0, $stock->sold_quantity);
        $this->assertSame(0, $stock->reserved_quantity);
        $this->assertSame(0.0, $this->supplierBalance($supplier->id));
    }

    public function test_seller_cannot_confirm_delivery_before_the_order_was_handed_over(): void
    {
        [$supplier, $seller, $customer, $order] = $this->sourcedOrder();

        // The order was never shipped - the line is still pending.
        $this->assertSame('pending', $order->items()->firstOrFail()->status);

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'delivered',
        ])->assertRedirect()->assertSessionHas('error');

        $order->refresh();
        $this->assertNotSame('delivered', $order->status);
        $this->assertSame('pending', $order->items()->firstOrFail()->status);

        // The supplier must not be settled for goods that were never handed over.
        $this->assertSame(0.0, $this->supplierBalance($supplier->id));
        $this->assertNull(ProcurementOrder::where('order_id', $order->id)->firstOrFail()->settled_at);
    }

    public function test_seller_can_confirm_delivery_once_the_supplier_shipped_the_order(): void
    {
        [$supplier, $seller, $customer, $order] = $this->sourcedOrder();

        // The supplier accepts and hands the goods over.
        $this->actingAs($supplier)->post(route('supplier.orders.accept', $order));
        $this->actingAs($supplier)->patch(route('supplier.orders.fulfill', $order), [
            'tracking_number' => 'TRK123',
        ]);

        $this->assertSame('shipped', $order->fresh('items')->items->firstOrFail()->status);

        // The seller may now confirm the delivery and the supplier is settled.
        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'delivered',
        ])->assertRedirect()->assertSessionMissing('error');

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame('delivered', $order->items()->firstOrFail()->status);
        $this->assertGreaterThan(0.0, $this->supplierBalance($supplier->id));
    }

    public function test_seller_can_still_mark_a_local_order_as_shipped(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => 50.00,
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 5]);

        $address = Address::create([
            'user_id'       => $customer->id,
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

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'shipped',
        ])->assertRedirect()->assertSessionMissing('error');

        $this->assertSame('shipped', $order->fresh('items')->items->firstOrFail()->status);
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_seller_cannot_change_a_refunded_order(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => 50.00,
        ]);

        $order = Order::create([
            'user_id'        => $customer->id,
            'subtotal'       => 50.00,
            'total'          => 50.00,
            'status'         => 'refunded',
            'payment_method' => 'cod',
            'payment_status' => 'refunded',
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => 50.00,
            'subtotal'     => 50.00,
            'status'       => 'delivered',
        ]);

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'shipped',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('delivered', $order->fresh('items')->items->firstOrFail()->status);
    }
}
