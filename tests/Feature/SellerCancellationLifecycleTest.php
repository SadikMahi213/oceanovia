<?php

namespace Tests\Feature;

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
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seller-initiated cancellation must go through the shared cancellation
 * lifecycle (stock restoration, source reservation release, commission
 * reversal, COD collection cancellation) exactly like the customer and
 * supplier paths, and must never leave a paid order recorded as cancelled.
 */
class SellerCancellationLifecycleTest extends TestCase
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

    private function sourcedProduct(User $supplier, User $seller, int $stock = 10, int $reserved = 0): array
    {
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
            'reserved_quantity'   => $reserved,
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

        return [$supplierProduct, $product];
    }

    /**
     * @param  array<int, array{product: Product, seller: User, supplier: User|null, quantity: int, price: float}>  $items
     */
    private function orderWithItems(User $customer, array $items, string $paymentStatus = 'pending', string $paymentMethod = 'cod'): Order
    {
        $order = Order::create([
            'user_id'        => $customer->id,
            'subtotal'       => array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items)),
            'total'          => array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items)),
            'status'         => 'confirmed',
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'confirmed_at'   => now(),
        ]);

        foreach ($items as $spec) {
            OrderItem::create([
                'order_id'            => $order->id,
                'product_id'          => $spec['product']->id,
                'seller_id'           => $spec['seller']->id,
                'supplier_id'         => $spec['supplier']?->id,
                'supplier_product_id' => $spec['supplier'] ? $spec['product']->supplier_product_id : null,
                'product_name'        => $spec['product']->name,
                'quantity'            => $spec['quantity'],
                'unit_price'          => $spec['price'],
                'subtotal'            => $spec['price'] * $spec['quantity'],
                'status'              => 'confirmed',
            ]);
        }

        return $order->fresh('items');
    }

    private function cancelAs(User $seller, Order $order): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'cancelled',
        ]);
    }

    public function test_seller_cancellation_restores_local_stock_exactly_once(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        // 9 left after the order consumed one unit.
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ]);

        $this->cancelAs($seller, $order)->assertRedirect();

        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame('cancelled', $order->fresh('items')->items->first()->status);
        $this->assertSame('cancelled', $order->fresh()->status);

        // A repeat must not restore the stock a second time.
        $this->cancelAs($seller, $order)->assertRedirect();

        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_seller_cancellation_reverses_the_commission_exactly_once(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ]);

        app(CommissionService::class)->createCommissions($order->fresh('items'));
        $this->assertSame(1, Commission::count());
        $this->assertSame('pending', Commission::firstOrFail()->status);

        $this->cancelAs($seller, $order)->assertRedirect();
        $this->cancelAs($seller, $order)->assertRedirect();

        $this->assertSame(1, Commission::count());
        $this->assertSame('reversed', Commission::firstOrFail()->status);
    }

    public function test_seller_cancellation_cancels_a_pending_cod_collection(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ], 'pending', 'cod');

        app(CodCollectionService::class)->createForOrder($order->fresh('items'));
        $this->assertSame('pending', CodCollection::firstOrFail()->status);

        $this->cancelAs($seller, $order)->assertRedirect();

        $this->assertSame('cancelled', CodCollection::firstOrFail()->status);
    }

    public function test_seller_cancellation_does_not_cancel_a_collected_cod_payment(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ], 'pending', 'cod');

        app(CodCollectionService::class)->createForOrder($order->fresh('items'));
        $collection = CodCollection::firstOrFail();
        $collection->update(['status' => 'collected', 'collected_amount' => $collection->expected_amount]);

        $this->cancelAs($seller, $order)->assertSessionHasErrors('cancel');

        $this->assertSame('collected', $collection->fresh()->status);
        $this->assertSame('confirmed', $order->fresh('items')->items->first()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_seller_cancellation_of_a_supplier_sourced_item_releases_the_reservation(): void
    {
        $seller = $this->seller();
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $product] = $this->sourcedProduct($supplier, $seller, 10, 1);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
        ]);

        $this->assertSame(1, SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)->firstOrFail()->reserved_quantity);

        $this->cancelAs($seller, $order)->assertRedirect();

        $this->assertSame('cancelled', $order->fresh('items')->items->first()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(
            0,
            SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)->firstOrFail()->reserved_quantity
        );
    }

    public function test_seller_cancellation_cannot_cancel_another_sellers_items(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $mine = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        $theirs = Product::factory()->create(['seller_id' => $otherSeller->id, 'price' => 50.00, 'status' => 'published']);
        Inventory::create(['product_id' => $mine->id, 'stock_quantity' => 9]);
        Inventory::create(['product_id' => $theirs->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $mine, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
            ['product' => $theirs, 'seller' => $otherSeller, 'supplier' => null, 'quantity' => 1, 'price' => 50.00],
        ]);

        $this->cancelAs($seller, $order)->assertRedirect();

        $items = $order->fresh('items')->items;
        $this->assertSame('cancelled', $items[0]->status);
        $this->assertSame('confirmed', $items[1]->status, 'Another seller item must not be cancelled.');
        $this->assertSame(10, Inventory::where('product_id', $mine->id)->firstOrFail()->stock_quantity);
        $this->assertSame(9, Inventory::where('product_id', $theirs->id)->firstOrFail()->stock_quantity);
    }

    public function test_partial_seller_cancellation_leaves_the_order_active(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $mine = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        $theirs = Product::factory()->create(['seller_id' => $otherSeller->id, 'price' => 50.00, 'status' => 'published']);
        Inventory::create(['product_id' => $mine->id, 'stock_quantity' => 9]);
        Inventory::create(['product_id' => $theirs->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $mine, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
            ['product' => $theirs, 'seller' => $otherSeller, 'supplier' => null, 'quantity' => 1, 'price' => 50.00],
        ]);

        $this->cancelAs($seller, $order)->assertRedirect();

        $order->refresh();
        $this->assertNotSame('cancelled', $order->status);
        $this->assertSame('confirmed', $order->status, 'The order stays active while another seller has items.');
        $this->assertNull($order->cancelled_at);
        $this->assertSame('confirmed', $order->fresh('items')->items[1]->status);
    }

    public function test_a_paid_order_cannot_be_cancelled_by_a_seller(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ], 'paid', 'stripe');

        $this->cancelAs($seller, $order)->assertRedirect();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->fresh('items')->items->first()->status);
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_seller_cancellation_of_a_paid_order_is_allowed_while_other_items_remain_active(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $mine = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        $theirs = Product::factory()->create(['seller_id' => $otherSeller->id, 'price' => 50.00, 'status' => 'published']);
        Inventory::create(['product_id' => $mine->id, 'stock_quantity' => 9]);
        Inventory::create(['product_id' => $theirs->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $mine, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
            ['product' => $theirs, 'seller' => $otherSeller, 'supplier' => null, 'quantity' => 1, 'price' => 50.00],
        ], 'paid', 'stripe');

        $this->cancelAs($seller, $order)->assertRedirect();

        $order->refresh();
        $this->assertNotSame('cancelled', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('cancelled', $order->fresh('items')->items[0]->status);
        $this->assertSame('confirmed', $order->fresh('items')->items[1]->status);
        $this->assertSame(10, Inventory::where('product_id', $mine->id)->firstOrFail()->stock_quantity);
    }

    public function test_rejected_seller_cancellation_leaves_every_record_unchanged(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ], 'paid', 'stripe');

        app(CommissionService::class)->createCommissions($order->fresh('items'));

        $this->cancelAs($seller, $order)->assertRedirect();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->fresh('items')->items->first()->status);
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame('pending', Commission::firstOrFail()->status);
        $this->assertSame(0, Commission::where('status', 'reversed')->count());
        $this->assertSame(0, CodCollection::count());
    }

    public function test_seller_status_transitions_other_than_cancelled_are_unchanged(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ]);

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'shipped',
        ])->assertRedirect();

        $this->assertSame('shipped', $order->fresh('items')->items->first()->status);
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->shipped_at);

        // The stock is untouched by a non-cancellation transition.
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'delivered',
        ])->assertRedirect();

        $this->assertSame('delivered', $order->fresh('items')->items->first()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_seller_cannot_update_an_order_they_do_not_own(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create(['seller_id' => $otherSeller->id, 'price' => 100.00, 'status' => 'published']);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $otherSeller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ]);

        $this->cancelAs($seller, $order)->assertForbidden();

        $this->assertSame('confirmed', $order->fresh('items')->items->first()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
    }
}
