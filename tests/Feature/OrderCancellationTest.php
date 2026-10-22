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
use App\Models\Refund;
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

    /*
    |--------------------------------------------------------------------------
    | Paid order cancellation must go through the refund workflow
    |--------------------------------------------------------------------------
    */

    private function paidOrderFor(User $customer, User $seller): Order
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);

        // The order was paid, so its stock was already deducted at checkout.
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = Order::create([
            'user_id'        => $customer->id,
            'subtotal'       => 100.00,
            'total'          => 100.00,
            'status'         => 'confirmed',
            'payment_method' => 'stripe',
            'payment_status' => 'paid',
            'confirmed_at'   => now(),
        ]);

        $item = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => 'Paid Product',
            'quantity'     => 1,
            'unit_price'   => 100.00,
            'subtotal'     => 100.00,
            'status'       => 'confirmed',
        ]);

        app(\App\Services\CommissionService::class)->createCommissions($order->fresh('items'));
        app(\App\Services\CodCollectionService::class)->createForOrder($order->fresh('items'));
        app(\App\Services\PayoutService::class)->credit($seller->id, 90.00);

        return $order->fresh('items');
    }

    public function test_customer_cannot_cancel_a_paid_order(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->paidOrderFor($customer, $seller);

        $balanceBefore = app(\App\Services\PayoutService::class)->getBalance($seller->id)->balance;

        $response = $this->actingAs($customer)->post(route('customer.orders.cancel', $order), [
            'reason' => 'Changed my mind',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertSame('confirmed', $order->status, 'A paid order must not be marked as cancelled.');
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->items()->firstOrFail()->status);

        // No cancellation side effects may be applied.
        $this->assertSame(9, Inventory::where('product_id', $order->items()->firstOrFail()->product_id)->firstOrFail()->stock_quantity);
        $this->assertSame('pending', Commission::firstOrFail()->status);
        $this->assertSame(0, CodCollection::count());
        $this->assertEquals((float) $balanceBefore, (float) app(\App\Services\PayoutService::class)->getBalance($seller->id)->balance);

        // The refund workflow is not bypassed or auto-triggered.
        $this->assertSame(0, Refund::count());
    }

    public function test_customer_cannot_cancel_another_customers_paid_order(): void
    {
        $seller = $this->seller();
        $owner = User::factory()->create(['role_type' => 'customer']);
        $intruder = User::factory()->create(['role_type' => 'customer']);
        $order = $this->paidOrderFor($owner, $seller);

        $this->actingAs($intruder)
            ->post(route('customer.orders.cancel', $order), ['reason' => 'not mine'])
            ->assertForbidden();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(9, Inventory::where('product_id', $order->items()->firstOrFail()->product_id)->firstOrFail()->stock_quantity);
    }

    public function test_repeated_cancellation_attempts_on_a_paid_order_have_no_side_effects(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->paidOrderFor($customer, $seller);

        $balanceBefore = (float) app(\App\Services\PayoutService::class)->getBalance($seller->id)->balance;

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($customer)
                ->post(route('customer.orders.cancel', $order), ['reason' => 'attempt '.$i])
                ->assertRedirect();
        }

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(0, Refund::count());
        $this->assertSame(1, Commission::count());
        $this->assertSame('pending', Commission::firstOrFail()->status);
        $this->assertSame(0, CodCollection::count());
        $this->assertEquals($balanceBefore, (float) app(\App\Services\PayoutService::class)->getBalance($seller->id)->balance);
    }

    public function test_cancellation_service_refuses_whole_order_cancellation_of_a_paid_order(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->paidOrderFor($customer, $seller);

        try {
            app(\App\Services\OrderCancellationService::class)->cancel($order, 'direct call', null, $customer->id);
            $this->fail('A paid order must not be cancellable.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('cancel', $e->errors());
        }

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->items()->firstOrFail()->status);
        $this->assertSame(9, Inventory::where('product_id', $order->items()->firstOrFail()->product_id)->firstOrFail()->stock_quantity);
        $this->assertSame('pending', Commission::firstOrFail()->status);
    }

    public function test_supplier_partial_rejection_of_a_paid_order_is_not_blocked(): void
    {
        // A supplier rejecting their own items does not change the order's
        // status, so it is not the paid-order cancellation path. This mirrors
        // the established behaviour in
        // test_supplier_reject_only_cancels_its_own_items.
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
            'payment_method' => 'stripe',
            'payment_status' => 'paid',
        ]);

        $item = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $sourced->id,
            'seller_id'    => $seller->id,
            'supplier_id'  => $supplier->id,
            'product_name' => 'Sourced',
            'quantity'     => 1,
            'unit_price'   => 70.00,
            'subtotal'     => 70.00,
            'status'       => 'processing',
        ]);

        OrderItem::create([
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

        $this->assertSame('cancelled', $item->fresh()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_customer_can_request_a_refund_for_a_paid_order_after_cancellation_is_refused(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->paidOrderFor($customer, $seller);

        $this->actingAs($customer)->post(route('customer.orders.cancel', $order), ['reason' => 'nope'])
            ->assertRedirect();

        $this->actingAs($customer)->post(route('refunds.store', $order), [
            'reason' => 'Item arrived damaged',
        ])->assertRedirect();

        $this->assertSame(1, Refund::count());
        $this->assertSame('pending', Refund::firstOrFail()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    /*
    |--------------------------------------------------------------------------
    | Seller / supplier rejection lifecycle
    |--------------------------------------------------------------------------
    */

    private function supplier(): User
    {
        return User::factory()->create(['role_type' => 'supplier']);
    }

    /**
     * A supplier-sourced product with its own source-stock row.
     *
     * @return array{0: \App\Models\SupplierProduct, 1: \App\Models\Product}
     */
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
     * An order carrying the given items.
     *
     * @param  array<int, array{product: \App\Models\Product, seller: \App\Models\User, supplier: \App\Models\User|null, quantity: int, price: float}>  $items
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

    public function test_supplier_can_reject_one_of_its_items_while_other_items_remain_active(): void
    {
        $supplier = $this->supplier();
        $otherSupplier = $this->supplier();
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $mine] = $this->sourcedProduct($supplier, $seller, 10, 1);
        [, $theirs] = $this->sourcedProduct($otherSupplier, $seller, 10, 1);

        $order = $this->orderWithItems($customer, [
            ['product' => $mine, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
            ['product' => $theirs, 'seller' => $seller, 'supplier' => $otherSupplier, 'quantity' => 1, 'price' => 79.99],
        ], 'paid', 'stripe');

        app(\App\Services\CommissionService::class)->createCommissions($order->fresh('items'));

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), [
            'reason' => 'Out of stock',
        ])->assertRedirect();

        $items = $order->fresh('items')->items;
        $this->assertSame('cancelled', $items[0]->status);
        $this->assertSame('confirmed', $items[1]->status, 'The other supplier keeps its item.');

        // The order is not cancelled, so the payment lifecycle is untouched.
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);

        // Only the rejecting supplier's commission is reversed.
        $this->assertSame('reversed', Commission::where('order_item_id', $items[0]->id)->firstOrFail()->status);
        $this->assertSame('pending', Commission::where('order_item_id', $items[1]->id)->firstOrFail()->status);
        $this->assertSame(1, Commission::where('status', 'reversed')->count());
        $this->assertSame(1, Commission::where('status', 'pending')->count());
    }

    public function test_supplier_can_reject_all_of_its_own_items_when_other_suppliers_items_remain_active(): void
    {
        $supplier = $this->supplier();
        $otherSupplier = $this->supplier();
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $first] = $this->sourcedProduct($supplier, $seller, 10, 2);
        [, $second] = $this->sourcedProduct($supplier, $seller, 10, 2);
        [, $theirs] = $this->sourcedProduct($otherSupplier, $seller, 10, 1);

        $order = $this->orderWithItems($customer, [
            ['product' => $first, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
            ['product' => $second, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
            ['product' => $theirs, 'seller' => $seller, 'supplier' => $otherSupplier, 'quantity' => 1, 'price' => 79.99],
        ], 'paid', 'stripe');

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), [
            'reason' => 'Out of stock',
        ])->assertRedirect();

        $items = $order->fresh('items')->items;
        $this->assertSame('cancelled', $items[0]->status);
        $this->assertSame('cancelled', $items[1]->status);
        $this->assertSame('confirmed', $items[2]->status);

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_supplier_rejection_that_would_leave_no_active_items_on_a_paid_order_is_refused(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $first] = $this->sourcedProduct($supplier, $seller, 10, 1);
        [, $second] = $this->sourcedProduct($supplier, $seller, 10, 1);

        $order = $this->orderWithItems($customer, [
            ['product' => $first, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
            ['product' => $second, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
        ], 'paid', 'stripe');

        app(\App\Services\CommissionService::class)->createCommissions($order->fresh('items'));

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), [
            'reason' => 'Out of stock',
        ])->assertSessionHasErrors('cancel');

        // The order must not become cancelled while the payment is paid.
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);

        // Nothing may be partially mutated: items, reservations, commissions.
        $items = $order->fresh('items')->items;
        $this->assertSame('confirmed', $items[0]->status);
        $this->assertSame('confirmed', $items[1]->status);
        $this->assertSame(1, SupplierProductStock::where('supplier_product_id', $first->supplier_product_id)->firstOrFail()->reserved_quantity);
        $this->assertSame(1, SupplierProductStock::where('supplier_product_id', $second->supplier_product_id)->firstOrFail()->reserved_quantity);
        $this->assertSame(0, Commission::where('status', 'reversed')->count());
        $this->assertSame(2, Commission::where('status', 'pending')->count());
    }

    public function test_supplier_rejection_that_would_leave_no_active_items_on_an_unpaid_order_is_allowed(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $product] = $this->sourcedProduct($supplier, $seller, 10, 1);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
        ], 'pending', 'cod');

        app(\App\Services\CommissionService::class)->createCommissions($order->fresh('items'));
        app(\App\Services\CodCollectionService::class)->createForOrder($order->fresh('items'));

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), [
            'reason' => 'Out of stock',
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $order->fresh('items')->items->first()->status);
        $this->assertSame('reversed', Commission::firstOrFail()->status);
        $this->assertSame('cancelled', CodCollection::firstOrFail()->status);
        $this->assertSame(0, SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)->firstOrFail()->reserved_quantity);
    }

    public function test_repeated_supplier_rejection_restores_stock_and_releases_reservation_exactly_once(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $sourced] = $this->sourcedProduct($supplier, $seller, 10, 1);

        // A supplier-fulfilled line that is served from local stock.
        $local = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 30.00,
            'status'    => 'published',
        ]);
        Inventory::create(['product_id' => $local->id, 'stock_quantity' => 4]);

        $order = $this->orderWithItems($customer, [
            ['product' => $sourced, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
            ['product' => $local, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 30.00],
        ], 'pending', 'cod');

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), ['reason' => 'Out of stock']);
        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), ['reason' => 'Out of stock']);

        $this->assertSame('cancelled', $order->fresh()->status);

        // The source reservation is released exactly once.
        $this->assertSame(0, SupplierProductStock::where('supplier_product_id', $sourced->supplier_product_id)->firstOrFail()->reserved_quantity);
        $this->assertSame(10, SupplierProductStock::where('supplier_product_id', $sourced->supplier_product_id)->firstOrFail()->stock_quantity);

        // The local stock is restored exactly once.
        $this->assertSame(5, Inventory::where('product_id', $local->id)->firstOrFail()->stock_quantity);
    }

    public function test_supplier_cannot_reject_items_owned_by_another_supplier(): void
    {
        $supplier = $this->supplier();
        $otherSupplier = $this->supplier();
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        [, $mine] = $this->sourcedProduct($supplier, $seller, 10, 1);
        [, $theirs] = $this->sourcedProduct($otherSupplier, $seller, 10, 1);

        $order = $this->orderWithItems($customer, [
            ['product' => $mine, 'seller' => $seller, 'supplier' => $supplier, 'quantity' => 1, 'price' => 79.99],
            ['product' => $theirs, 'seller' => $seller, 'supplier' => $otherSupplier, 'quantity' => 1, 'price' => 79.99],
        ], 'pending', 'cod');

        $this->actingAs($supplier)->post(route('supplier.orders.reject', $order), ['reason' => 'Out of stock'])
            ->assertRedirect();

        $items = $order->fresh('items')->items;
        $this->assertSame('cancelled', $items[0]->status);
        $this->assertSame('confirmed', $items[1]->status, 'Another supplier item must not be cancelled.');
        $this->assertSame(1, SupplierProductStock::where('supplier_product_id', $theirs->supplier_product_id)->firstOrFail()->reserved_quantity);
    }

    public function test_seller_cannot_cancel_a_paid_order_that_would_be_cancelled_entirely(): void
    {
        $seller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 9]);

        $order = $this->orderWithItems($customer, [
            ['product' => $product, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
        ], 'paid', 'stripe');

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'cancelled',
        ])->assertRedirect();

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh('items')->items->first()->status);
        $this->assertSame(9, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_seller_can_still_update_a_paid_order_that_is_not_cancelled_entirely(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = User::factory()->create(['role_type' => 'customer']);

        $mine = Product::factory()->create(['seller_id' => $seller->id, 'price' => 100.00, 'status' => 'published']);
        $theirs = Product::factory()->create(['seller_id' => $otherSeller->id, 'price' => 50.00, 'status' => 'published']);

        $order = $this->orderWithItems($customer, [
            ['product' => $mine, 'seller' => $seller, 'supplier' => null, 'quantity' => 1, 'price' => 100.00],
            ['product' => $theirs, 'seller' => $otherSeller, 'supplier' => null, 'quantity' => 1, 'price' => 50.00],
        ], 'paid', 'stripe');

        $this->actingAs($seller)->patch(route('seller.orders.update-status', $order), [
            'status' => 'delivered',
        ])->assertRedirect();

        $items = $order->fresh('items')->items;
        $this->assertSame('delivered', $items[0]->status);
        $this->assertSame('confirmed', $items[1]->status);
        $this->assertNotSame('cancelled', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}

