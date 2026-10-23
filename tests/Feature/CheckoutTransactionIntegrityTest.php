<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Jobs\ProcessOrder;
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
use App\Models\ProcurementOrder;
use App\Models\SellerProfile;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * F-06.2 - checkout transaction integrity.
 *
 * The checkout locks the parent cart row, then the cart item rows by
 * ascending id, and derives the order, the order items, the totals and the
 * stock movement from those locked rows. Every database write that a valid
 * checkout needs is committed by that one transaction, while the notification
 * and the audit work stays outside it.
 *
 * SCOPE OF THESE TESTS
 * --------------------
 * They run on SQLite in-memory. The SQLite grammar compiles lockForUpdate()
 * to an empty string and every connection gets its own private database, so
 * the lock assertions below are protocol assertions: they prove which rows the
 * checkout locks and in which order. They do NOT establish MySQL/InnoDB
 * row-lock semantics and they are NOT proof of simultaneous-request safety.
 * That requires a real MySQL engine with two independent connections.
 */
class CheckoutTransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The tables whose row was locked, in the order the lock was requested.
     *
     * @var array<int, string>
     */
    public static array $lockCalls = [];

    /** @var array<int, string> */
    private array $queries = [];

    /**
     * Intercept lockForUpdate() through the Eloquent builder macro API.
     *
     * SQLite renders no lock clause, so the compiled SQL cannot show that a
     * lock was requested. The macro reproduces lockForUpdate() exactly: it
     * sets the same query builder flag the framework method sets, so the
     * executed SQL is unchanged.
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$lockCalls = [];

        Builder::macro('lockForUpdate', function () {
            // The macro is bound to the Eloquent builder, so the class name is
            // used explicitly instead of self::.
            CheckoutTransactionIntegrityTest::$lockCalls[] = $this->getModel()->getTable();

            $this->query->lock = true;

            return $this;
        });
    }

    protected function tearDown(): void
    {
        $this->forgetLockMacro();

        self::$lockCalls = [];

        parent::tearDown();
    }

    /**
     * Remove the lock spy again so no other test is affected by it.
     */
    private function forgetLockMacro(): void
    {
        $property = new \ReflectionProperty(Builder::class, 'macros');
        $property->setAccessible(true);

        $macros = $property->getValue();

        unset($macros['lockForUpdate']);

        $property->setValue(null, $macros);
    }

    /**
     * Record every statement the connection runs, so the order of the
     * statements can be asserted.
     */
    private function captureQueries(): void
    {
        $this->queries = [];

        DB::connection()->beforeExecuting(function ($query): void {
            $this->queries[] = strtolower(str_replace('`', '"', (string) $query));
        });
    }

    private function firstQueryMatching(string $pattern): ?int
    {
        foreach ($this->queries as $index => $sql) {
            if (str_contains($sql, $pattern)) {
                return $index;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

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

    private function fillCart(User $customer, Product $product, int $quantity = 1, ?float $unitPrice = null): Cart
    {
        $cart = Cart::create(['user_id' => $customer->id]);

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $unitPrice ?? $product->price,
        ]);

        return $cart;
    }

    /**
     * A product that is sourced from a supplier, so the checkout reserves
     * supplier source stock and opens a procurement order.
     */
    private function sourcedProductWithStock(User $seller, int $stock = 10, float $price = 25.00): Product
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $category = Category::factory()->create(['status' => true]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => 10.00,
            'status'          => 'published',
        ]);

        SupplierProductStock::create([
            'supplier_product_id' => $supplierProduct->id,
            'stock_quantity'      => $stock,
        ]);

        return Product::factory()->create([
            'seller_id'           => $seller->id,
            'category_id'         => $category->id,
            'status'              => 'published',
            'price'               => $price,
            'supplier_product_id' => $supplierProduct->id,
            'sourcing_price'      => 10.00,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Locking protocol
    |
    | These assert which rows the checkout locks and in which order. They are
    | protocol assertions, not simultaneous-request tests.
    |--------------------------------------------------------------------------
    */

    public function test_web_checkout_locks_the_parent_cart_then_the_cart_items_then_the_stock(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 2);

        $this->captureQueries();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        $this->assertSame(
            ['carts', 'cart_items', 'inventory'],
            self::$lockCalls,
            'The checkout must lock the parent cart, then the cart items, then the stock rows.'
        );

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemRead = $this->firstQueryMatching('from "cart_items"');
        $itemWrite = $this->firstQueryMatching('insert into "order_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemRead, 'The cart item rows were never read.');
        $this->assertNotNull($itemWrite, 'No order item was written.');

        $this->assertLessThan($itemRead, $cartRead, 'The parent cart row must be locked before the cart item rows.');
        $this->assertLessThan($itemWrite, $itemRead, 'The cart item rows must be locked before the order is built from them.');
        $this->assertStringContainsString(
            'order by "id" asc',
            $this->queries[$itemRead],
            'The cart item rows must be locked by ascending id.'
        );
    }

    public function test_api_checkout_locks_the_parent_cart_then_the_cart_items_then_the_stock(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 2);

        Sanctum::actingAs($customer);

        $this->captureQueries();

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $this->assertSame(
            ['carts', 'cart_items', 'inventory'],
            self::$lockCalls,
            'The checkout must lock the parent cart, then the cart items, then the stock rows.'
        );

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemRead = $this->firstQueryMatching('from "cart_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemRead, 'The cart item rows were never read.');
        $this->assertLessThan($itemRead, $cartRead, 'The parent cart row must be locked before the cart item rows.');
        $this->assertStringContainsString(
            'order by "id" asc',
            $this->queries[$itemRead],
            'The cart item rows must be locked by ascending id.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The order is built from the persisted cart rows
    |--------------------------------------------------------------------------
    */

    public function test_the_order_totals_and_items_use_the_persisted_cart_values(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10, 25.00);

        // The cart was filled at 15.00 per unit, the product now costs 25.00.
        // The checkout must use what the cart row says, not the product table.
        $this->fillCart($customer, $product, 2, 15.00);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        $order = Order::firstOrFail();
        $orderItem = OrderItem::firstOrFail();

        $this->assertSame(30.00, (float) $order->subtotal);
        $this->assertSame(15.00, (float) $orderItem->unit_price);
        $this->assertSame(2, $orderItem->quantity);
        $this->assertSame(30.00, (float) $orderItem->subtotal);
        $this->assertSame(
            (float) $order->subtotal + (float) $order->shipping_cost + (float) $order->tax - (float) $order->discount,
            (float) $order->total
        );
    }

    public function test_the_api_checkout_totals_and_items_use_the_persisted_cart_values(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10, 25.00);

        $this->fillCart($customer, $product, 3, 12.00);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $order = Order::firstOrFail();
        $orderItem = OrderItem::firstOrFail();

        $this->assertSame(36.00, (float) $order->subtotal);
        $this->assertSame(12.00, (float) $orderItem->unit_price);
        $this->assertSame(3, $orderItem->quantity);
        $this->assertSame(0, CartItem::count());
    }

    /*
    |--------------------------------------------------------------------------
    | The transaction boundary of the post-commit side effects
    |--------------------------------------------------------------------------
    */

    public function test_the_order_placed_event_is_dispatched_after_the_transaction_committed(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        // Remove the application listener so only the probe below runs.
        Event::forget(OrderPlaced::class);

        $levels = [];
        $insideTransaction = [];
        $orderWasVisible = [];

        // The nesting level while the checkout transaction is still open.
        Event::listen('eloquent.creating: '.OrderItem::class, function () use (&$insideTransaction): void {
            $insideTransaction[] = DB::connection()->transactionLevel();
        });

        Event::listen(OrderPlaced::class, function (OrderPlaced $event) use (&$levels, &$orderWasVisible): void {
            $levels[] = DB::connection()->transactionLevel();
            $orderWasVisible[] = Order::whereKey($event->order->id)->exists();
        });

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        $this->assertNotEmpty($levels, 'The OrderPlaced event must be dispatched.');
        $this->assertNotEmpty($insideTransaction, 'The order was not created inside the checkout transaction.');

        // The controller dispatches the event after the checkout transaction
        // has ended, so the dispatch runs at a shallower nesting level than
        // the writes of the checkout itself. The queued ProcessOrder job
        // dispatches the event again by design, inside the job's own
        // transaction, so only the first dispatch belongs to the checkout.
        $this->assertLessThan(
            $insideTransaction[0],
            $levels[0],
            'The controller must dispatch the event outside the checkout transaction.'
        );

        // Whichever dispatch runs, the order row already exists.
        $this->assertSame(
            array_fill(0, count($orderWasVisible), true),
            $orderWasVisible,
            'The event must never be dispatched before the order was written.'
        );
    }

    public function test_the_process_order_job_and_the_event_are_dispatched_exactly_once(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        Event::fake([OrderPlaced::class]);
        Queue::fake();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        Event::assertDispatched(OrderPlaced::class, 1);
        Queue::assertPushed(ProcessOrder::class, 1);
    }

    public function test_no_event_or_job_is_dispatched_when_the_checkout_rolls_back(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 1);

        // More than the available stock: the item is held, not rejected.
        $this->fillCart($customer, $product, 5);

        Event::fake([OrderPlaced::class]);
        Queue::fake();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        Event::assertDispatched(OrderPlaced::class);
        Queue::assertPushed(ProcessOrder::class);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(0, CartItem::count());
        $this->assertSame(0, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    /*
    |--------------------------------------------------------------------------
    | One transaction commits the whole checkout
    |--------------------------------------------------------------------------
    */

    public function test_a_committed_checkout_persists_the_whole_lifecycle_together(): void
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
        ])->assertStatus(302);

        $order = Order::firstOrFail();

        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNotNull($order->confirmed_at);

        $this->assertSame(1, OrderItem::count());
        $this->assertSame(1, Commission::count());
        $this->assertSame(1, CodCollection::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(0, CartItem::count());

        // This product is not linked to a supplier product, so no procurement
        // order is opened for it.
        $this->assertSame(0, ProcurementOrder::count());
    }

    public function test_a_committed_sourced_checkout_persists_the_procurement_order_and_the_source_reservation(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->sourcedProductWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        $order = Order::firstOrFail();

        $this->assertSame('confirmed', $order->status);
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(1, ProcurementOrder::count());
        $this->assertSame(1, CodCollection::count());

        // The supplier source stock is reserved, the local listing row is
        // left alone because a sourced item is not decremented locally.
        $sourceStock = SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)->firstOrFail();
        $this->assertSame(2, $sourceStock->reserved_quantity);
        $this->assertSame(10, $sourceStock->stock_quantity);
        $this->assertSame(0, CartItem::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Response contracts
    |--------------------------------------------------------------------------
    */

    public function test_the_api_checkout_response_contract_is_unchanged(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'user_id', 'subtotal', 'total', 'status', 'payment_method', 'payment_status', 'items'],
            'payment_url',
        ]);

        $response->assertJsonPath('message', 'Order placed successfully. Payment will be collected on delivery.');
        $response->assertJsonPath('data.payment_method', 'cod');
    }

    public function test_an_unavailable_payment_method_cannot_create_an_order(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'stripe',
            'billing_same'        => '1',
        ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(1, CartItem::count());
        $this->assertSame(10, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_an_unavailable_payment_method_cannot_create_an_order_through_the_api(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'stripe',
        ])->assertStatus(422)->assertJsonValidationErrors('payment_method');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(1, CartItem::count());
    }
}
