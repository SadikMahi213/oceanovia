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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * F-06.1 - cart mutation locking.
 *
 * The web and API cart endpoints now take the parent cart row lock inside a
 * transaction before they read or change any cart item row, so a cart
 * mutation and a checkout for the same user serialise on that parent row.
 * The lock order is carts -> cart_items (ascending id), which is the order
 * the checkout path already uses for the cart.
 *
 * SCOPE OF THESE TESTS
 * --------------------
 * They run on SQLite in-memory. The SQLite grammar compiles lockForUpdate()
 * to an empty string and every connection gets its own private database, so
 * these tests assert the locking *protocol* (which row is read first, which
 * clause is emitted when the engine supports one) and the atomicity of each
 * mutation. They do NOT establish MySQL/InnoDB row-lock semantics and they
 * are NOT proof of simultaneous-request safety. That requires a real MySQL
 * engine with two independent connections.
 */
class CartMutationLockingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $queries = [];

    /**
     * The tables whose row was locked, in the order the lock was requested.
     *
     * @var array<int, string>
     */
    public static array $lockCalls = [];

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
            CartMutationLockingTest::$lockCalls[] = $this->getModel()->getTable();

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

    /*
    |--------------------------------------------------------------------------
    | Helpers
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

    private function product(User $seller, float $price = 19.99, int $stock = 10): Product
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

    private function cartFor(User $user, Product $product, int $quantity = 1): Cart
    {
        $cart = Cart::create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $cart;
    }

    private function orderFor(User $customer, Product $product, int $quantity = 1): Order
    {
        return $this->orderWithItems($customer, [[$product, $quantity]]);
    }

    /**
     * @param  array<int, array{0: Product, 1: int}>  $items
     */
    private function orderWithItems(User $customer, array $items): Order
    {
        $order = Order::create([
            'user_id'  => $customer->id,
            'subtotal' => 0,
            'total'    => 0,
            'status'   => 'confirmed',
        ]);

        $subtotal = 0;

        foreach ($items as [$product, $quantity]) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'seller_id'    => $product->seller_id,
                'product_name' => $product->name,
                'sku'          => $product->sku,
                'quantity'     => $quantity,
                'unit_price'   => $product->price,
                'subtotal'     => $product->price * $quantity,
            ]);

            $subtotal += $product->price * $quantity;
        }

        $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);

        return $order;
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

    /**
     * Record every statement the connection runs, so the order of the
     * statements can be asserted.
     */
    private function captureQueries(): void
    {
        $this->queries = [];

        DB::connection()->beforeExecuting(function ($query): void {
            $this->queries[] = $this->normalise((string) $query);
        });
    }

    private function normalise(string $sql): string
    {
        return strtolower(str_replace('`', '"', $sql));
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

    /**
     * Assert the exact sequence of row locks the request requested.
     *
     * @param  array<int, string>  $expected
     */
    private function assertLockOrder(array $expected): void
    {
        $this->assertSame(
            $expected,
            self::$lockCalls,
            'The rows must be locked in the order: '.implode(' -> ', $expected).'.'
        );
    }

    private function supportsRowLockClause(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }

    /*
    |--------------------------------------------------------------------------
    | Web cart behaviour is preserved
    |--------------------------------------------------------------------------
    */

    public function test_web_add_persists_the_item_with_the_requested_quantity(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 2,
        ])->assertStatus(200)->assertJson(['cart_count' => 2]);

        $this->assertSame(1, CartItem::count());

        $item = CartItem::firstOrFail();

        $this->assertSame($product->id, $item->product_id);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(19.99, (float) $item->unit_price);
        $this->assertSame($user->id, $item->cart->user_id);
    }

    public function test_web_add_of_the_same_product_increments_the_existing_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 2,
        ])->assertStatus(200);

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 3,
        ])->assertStatus(200)->assertJson(['cart_count' => 5]);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(5, CartItem::firstOrFail()->quantity);
    }

    public function test_web_update_persists_the_new_quantity_on_the_existing_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($user, $product, 2);

        $this->actingAs($user)->postJson(route('cart.update'), [
            'cart_item_id' => $cart->items()->firstOrFail()->id,
            'quantity'     => 7,
        ])->assertStatus(200)->assertJson(['cart_count' => 7]);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(7, CartItem::firstOrFail()->quantity);
    }

    public function test_web_update_of_an_unknown_item_is_rejected_and_leaves_the_cart_unchanged(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $this->cartFor($user, $product, 2);

        $this->actingAs($user)->postJson(route('cart.update'), [
            'cart_item_id' => 999999,
            'quantity'     => 7,
        ])->assertRedirect()->assertSessionHasErrors('cart_item_id');

        $this->assertSame(2, CartItem::firstOrFail()->quantity);
    }

    public function test_web_update_of_another_users_item_returns_404_and_changes_nothing(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($owner, $product, 2);

        $this->actingAs($other)->postJson(route('cart.update'), [
            'cart_item_id' => $cart->items()->firstOrFail()->id,
            'quantity'     => 7,
        ])->assertStatus(404);

        $this->assertSame(2, CartItem::firstOrFail()->quantity);
    }

    public function test_web_remove_deletes_only_the_targeted_item(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $product = $this->product($seller);
        $other = $this->product($seller);

        $cart = Cart::create(['user_id' => $user->id]);
        $keep = CartItem::create(['cart_id' => $cart->id, 'product_id' => $other->id, 'quantity' => 1, 'unit_price' => $other->price]);
        $drop = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => $product->price]);

        $this->actingAs($user)->deleteJson(route('cart.remove', $drop->id))
            ->assertStatus(200)
            ->assertJson(['cart_count' => 1]);

        $this->assertSame(1, CartItem::count());
        $this->assertSame($keep->id, CartItem::firstOrFail()->id);
    }

    public function test_web_remove_of_an_unknown_item_keeps_the_cart_unchanged(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $this->cartFor($user, $product, 2);

        $this->actingAs($user)->deleteJson(route('cart.remove', 999999))
            ->assertStatus(200)
            ->assertJson(['cart_count' => 2]);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(2, CartItem::firstOrFail()->quantity);
    }

    public function test_web_remove_without_a_cart_returns_the_empty_summary(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->deleteJson(route('cart.remove', 1))
            ->assertStatus(200)
            ->assertExactJson(['cart_count' => 0, 'total' => 0]);

        $this->assertSame(0, CartItem::count());
    }

    public function test_web_sync_replaces_the_cart_contents_with_the_incoming_list(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $stale = $this->product($seller);
        $kept = $this->product($seller);
        $added = $this->product($seller);

        $cart = Cart::create(['user_id' => $user->id]);
        $staleItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $stale->id, 'quantity' => 4, 'unit_price' => $stale->price]);
        $keptItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $kept->id, 'quantity' => 1, 'unit_price' => $kept->price]);

        $this->actingAs($user)->postJson(route('cart.sync'), [
            'items' => [
                ['id' => $kept->id, 'quantity' => 6],
                ['id' => $added->id, 'quantity' => 2],
            ],
        ])->assertStatus(200)->assertJson(['synced' => true, 'cart_count' => 8]);

        $this->assertSame(2, CartItem::count());
        $this->assertNull(CartItem::find($staleItem->id));

        $this->assertSame(6, CartItem::find($keptItem->id)->quantity);
        $this->assertSame(2, CartItem::where('product_id', $added->id)->firstOrFail()->quantity);
    }

    /*
    |--------------------------------------------------------------------------
    | API cart behaviour is preserved
    |--------------------------------------------------------------------------
    */

    public function test_api_add_persists_the_item_with_the_requested_quantity(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/add', [
            'product_id' => $product->id,
            'quantity'    => 2,
        ])->assertStatus(200)->assertJsonPath('data.items_count', 2);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(2, CartItem::firstOrFail()->quantity);
        $this->assertSame($user->id, CartItem::firstOrFail()->cart->user_id);
    }

    public function test_api_add_of_the_same_product_increments_the_existing_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/add', [
            'product_id' => $product->id,
            'quantity'    => 2,
        ])->assertStatus(200);

        $this->postJson('/api/v1/cart/add', [
            'product_id' => $product->id,
            'quantity'    => 3,
        ])->assertStatus(200)->assertJsonPath('data.items_count', 5);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(5, CartItem::firstOrFail()->quantity);
    }

    public function test_api_update_persists_the_new_quantity(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($user, $product, 2);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/update', [
            'cart_item_id' => $cart->items()->firstOrFail()->id,
            'quantity'     => 7,
        ])->assertStatus(200)->assertJsonPath('data.items_count', 7);

        $this->assertSame(7, CartItem::firstOrFail()->quantity);
    }

    public function test_api_update_with_quantity_zero_deletes_the_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($user, $product, 2);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/update', [
            'cart_item_id' => $cart->items()->firstOrFail()->id,
            'quantity'     => 0,
        ])->assertStatus(200)->assertJsonPath('data.items_count', 0);

        $this->assertSame(0, CartItem::count());
    }

    public function test_api_update_of_another_users_item_returns_404_and_changes_nothing(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($owner, $product, 2);

        Sanctum::actingAs($other);

        $this->postJson('/api/v1/cart/update', [
            'cart_item_id' => $cart->items()->firstOrFail()->id,
            'quantity'     => 7,
        ])->assertStatus(404);

        $this->assertSame(2, CartItem::firstOrFail()->quantity);
    }

    public function test_api_remove_deletes_the_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($user, $product, 2);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/cart/'.$cart->items()->firstOrFail()->id)
            ->assertStatus(200)
            ->assertJsonPath('data.items_count', 0);

        $this->assertSame(0, CartItem::count());
    }

    public function test_api_remove_of_an_unknown_item_returns_404(): void
    {
        $user = $this->customer();
        $this->product($this->seller());

        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/cart/999999')->assertStatus(404);

        $this->assertSame(0, CartItem::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout interaction
    |--------------------------------------------------------------------------
    */

    public function test_checkout_persists_the_quantity_it_consumed_from_the_cart(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->product($seller, 25.00, 10);

        $this->cartFor($customer, $product, 3);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());

        $orderItem = OrderItem::firstOrFail();

        $this->assertSame(3, $orderItem->quantity);
        $this->assertSame(25.00, (float) $orderItem->unit_price);

        // The consumed cart is emptied, and the stock movement is committed.
        $this->assertSame(0, CartItem::count());
        $this->assertSame(7, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_cart_add_after_a_checkout_does_not_change_the_ordered_state(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->product($seller, 25.00, 10);

        $this->cartFor($customer, $product, 3);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        $orderId = Order::firstOrFail()->id;

        // A later cart change must not rewrite what was already ordered.
        $this->actingAs($customer)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 2,
        ])->assertStatus(200)->assertJson(['cart_count' => 2]);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame($orderId, Order::firstOrFail()->id);
        $this->assertSame(3, OrderItem::firstOrFail()->quantity);
        $this->assertSame(7, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        // The new item is a fresh cart row, not the consumed one.
        $this->assertSame(1, CartItem::count());
        $this->assertSame(2, CartItem::firstOrFail()->quantity);
    }

    public function test_cart_update_after_a_checkout_cannot_change_the_ordered_state(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->product($seller, 25.00, 10);

        $cart = $this->cartFor($customer, $product, 3);
        $cartItemId = $cart->items()->firstOrFail()->id;

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        // The consumed item no longer exists, so the mutation is rejected.
        $this->actingAs($customer)->postJson(route('cart.update'), [
            'cart_item_id' => $cartItemId,
            'quantity'     => 9,
        ])->assertRedirect()->assertSessionHasErrors('cart_item_id');

        $this->assertSame(1, Order::count());
        $this->assertSame(3, OrderItem::firstOrFail()->quantity);
        $this->assertSame(0, CartItem::count());
    }

    /*
    |--------------------------------------------------------------------------
    | A failed mutation leaves the persisted cart untouched
    |--------------------------------------------------------------------------
    */

    public function test_a_failed_web_cart_item_update_leaves_the_persisted_quantity_unchanged(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($user, $product, 3);

        Event::listen('eloquent.updating: '.CartItem::class, function (): void {
            throw new RuntimeException('cart item update failed');
        });

        $this->withoutExceptionHandling();

        $thrown = null;

        try {
            $this->actingAs($user)->postJson(route('cart.update'), [
                'cart_item_id' => $cart->items()->firstOrFail()->id,
                'quantity'     => 5,
            ]);
        } catch (RuntimeException $e) {
            $thrown = $e;
        } finally {
            Event::forget('eloquent.updating: '.CartItem::class);
        }

        $this->assertNotNull($thrown, 'The cart mutation was expected to fail.');
        $this->assertSame('cart item update failed', $thrown->getMessage());

        $this->assertSame(1, CartItem::count());
        $this->assertSame(3, CartItem::firstOrFail()->quantity);
    }

    public function test_a_failed_api_cart_item_insert_leaves_the_cart_without_a_new_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        Sanctum::actingAs($user);

        Event::listen('eloquent.creating: '.CartItem::class, function (): void {
            throw new RuntimeException('cart item insert failed');
        });

        $this->withoutExceptionHandling();

        $thrown = null;

        try {
            $this->postJson('/api/v1/cart/add', [
                'product_id' => $product->id,
                'quantity'    => 2,
            ]);
        } catch (RuntimeException $e) {
            $thrown = $e;
        } finally {
            Event::forget('eloquent.creating: '.CartItem::class);
        }

        $this->assertNotNull($thrown, 'The cart mutation was expected to fail.');
        $this->assertSame('cart item insert failed', $thrown->getMessage());

        $this->assertSame(0, CartItem::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Locking protocol
    |
    | These assert which rows are locked and in which order. The lock is read
    | from the query builder, so the assertion holds on every driver; the
    | FOR UPDATE clause itself is only asserted on engines that render one.
    | They are protocol assertions, not simultaneous-request tests.
    |--------------------------------------------------------------------------
    */

    public function test_web_cart_add_takes_the_parent_cart_lock_before_it_writes_a_cart_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        $this->captureQueries();

        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 1,
        ])->assertStatus(200);

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemWrite = $this->firstQueryMatching('insert into "cart_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemWrite, 'The cart item was never written.');

        // The parent cart row is locked first, the cart item row second.
        $this->assertLockOrder(['carts', 'cart_items']);

        $this->assertLessThan(
            $itemWrite,
            $cartRead,
            'The parent cart row must be locked before the cart item row is written.'
        );

        if ($this->supportsRowLockClause()) {
            $this->assertStringContainsString(
                'for update',
                $this->queries[$cartRead],
                'The parent cart row must be locked with FOR UPDATE.'
            );
        }
    }

    public function test_api_cart_add_takes_the_parent_cart_lock_before_it_writes_a_cart_item(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());

        Sanctum::actingAs($user);

        $this->captureQueries();

        $this->postJson('/api/v1/cart/add', [
            'product_id' => $product->id,
            'quantity'    => 1,
        ])->assertStatus(200);

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemWrite = $this->firstQueryMatching('insert into "cart_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemWrite, 'The cart item was never written.');

        // The parent cart row is locked first, the cart item row second.
        $this->assertLockOrder(['carts', 'cart_items']);

        $this->assertLessThan(
            $itemWrite,
            $cartRead,
            'The parent cart row must be locked before the cart item row is written.'
        );

        if ($this->supportsRowLockClause()) {
            $this->assertStringContainsString(
                'for update',
                $this->queries[$cartRead],
                'The parent cart row must be locked with FOR UPDATE.'
            );
        }
    }

    public function test_web_cart_update_locks_the_cart_item_row_before_it_changes_it(): void
    {
        $user = $this->customer();
        $product = $this->product($this->seller());
        $cart = $this->cartFor($user, $product, 2);

        $this->captureQueries();

        $this->actingAs($user)->postJson(route('cart.update'), [
            'cart_item_id' => $cart->items()->firstOrFail()->id,
            'quantity'     => 7,
        ])->assertStatus(200);

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemRead = $this->firstQueryMatching('from "cart_items" where "id" = ? and "cart_id" = ?');
        $itemWrite = $this->firstQueryMatching('update "cart_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemRead, 'The cart item row was never read under a lock.');
        $this->assertNotNull($itemWrite, 'The cart item was never updated.');

        // The parent cart row is locked first, the cart item row second.
        $this->assertLockOrder(['carts', 'cart_items']);

        $this->assertLessThan(
            $itemRead,
            $cartRead,
            'The parent cart row must be locked before the cart item row.'
        );
        $this->assertLessThan(
            $itemWrite,
            $itemRead,
            'The cart item row must be locked before it is changed.'
        );

        if ($this->supportsRowLockClause()) {
            $this->assertStringContainsString(
                'for update',
                $this->queries[$itemRead],
                'The cart item row must be locked with FOR UPDATE.'
            );
        }
    }

    public function test_web_cart_sync_locks_the_existing_cart_items_in_ascending_id_order(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $first = $this->product($seller);
        $second = $this->product($seller);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $first->id, 'quantity' => 1, 'unit_price' => $first->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $second->id, 'quantity' => 1, 'unit_price' => $second->price]);

        $this->captureQueries();

        $this->actingAs($user)->postJson(route('cart.sync'), [
            'items' => [
                ['id' => $first->id, 'quantity' => 4],
            ],
        ])->assertStatus(200);

        $orderedLock = null;

        foreach ($this->queries as $index => $sql) {
            if (str_contains($sql, 'from "cart_items"') && str_contains($sql, 'order by "id" asc')) {
                $orderedLock = $index;
                break;
            }
        }

        $this->assertNotNull(
            $orderedLock,
            'The existing cart items must be locked in ascending id order.'
        );

        // The parent cart row is locked first, the cart item rows second.
        $this->assertLockOrder(['carts', 'cart_items']);

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemDelete = $this->firstQueryMatching('delete from "cart_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemDelete, 'No cart item was deleted.');

        $this->assertLessThan($orderedLock, $cartRead, 'The parent cart row must be locked first.');
        $this->assertLessThan($itemDelete, $orderedLock, 'The cart item rows must be locked before they are deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Reordering from an order page
    |
    | The reorder is a cart mutation, so it follows the same protocol as the
    | cart endpoints: the parent cart row is locked first, then the affected
    | cart item rows by ascending id, and the whole mutation is atomic.
    |--------------------------------------------------------------------------
    */

    public function test_reorder_locks_the_parent_cart_then_the_cart_items_in_ascending_id_order(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $product = $this->product($seller);
        $order = $this->orderFor($user, $product, 2);

        $this->captureQueries();

        $this->actingAs($user)->post(route('customer.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success', 'Items added to cart.');

        // The parent cart row is locked first, the cart item rows second.
        $this->assertLockOrder(['carts', 'cart_items']);

        $cartRead = $this->firstQueryMatching('select * from "carts"');
        $itemRead = $this->firstQueryMatching('from "cart_items"');

        $this->assertNotNull($cartRead, 'The parent cart row was never read.');
        $this->assertNotNull($itemRead, 'The cart item rows were never read.');
        $this->assertLessThan(
            $itemRead,
            $cartRead,
            'The parent cart row must be locked before the cart item rows.'
        );
        $this->assertStringContainsString(
            'order by "id" asc',
            $this->queries[$itemRead],
            'The cart item rows must be locked by ascending id.'
        );

        if ($this->supportsRowLockClause()) {
            $this->assertStringContainsString(
                'for update',
                $this->queries[$itemRead],
                'The cart item row must be locked with FOR UPDATE.'
            );
        }
    }

    public function test_reorder_increments_an_existing_cart_item_instead_of_creating_a_duplicate_row(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $product = $this->product($seller);
        $order = $this->orderFor($user, $product, 3);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'unit_price' => $product->price,
        ]);

        $this->actingAs($user)->post(route('customer.orders.reorder', $order))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(1, CartItem::count());
        $this->assertSame(5, CartItem::firstOrFail()->quantity);
    }

    public function test_reorder_inserts_the_expected_cart_item_for_a_new_product(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $product = $this->product($seller);
        $order = $this->orderFor($user, $product, 3);

        // The customer has no cart yet, so the reorder creates it.
        $this->actingAs($user)->post(route('customer.orders.reorder', $order))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(1, Cart::count());
        $this->assertSame($user->id, Cart::firstOrFail()->user_id);
        $this->assertSame(1, CartItem::count());

        $item = CartItem::firstOrFail();

        $this->assertSame($product->id, $item->product_id);
        $this->assertSame(3, $item->quantity);
        $this->assertSame(19.99, (float) $item->unit_price);
    }

    public function test_reorder_persists_every_item_of_the_order(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $first = $this->product($seller);
        $second = $this->product($seller, 29.50);

        $order = $this->orderWithItems($user, [[$first, 2], [$second, 4]]);

        // The second product is already in the cart.
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $second->id,
            'quantity'   => 1,
            'unit_price' => $second->price,
        ]);

        $this->actingAs($user)->post(route('customer.orders.reorder', $order))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(2, CartItem::count());

        $this->assertSame(2, CartItem::where('product_id', $first->id)->firstOrFail()->quantity);
        $this->assertSame(5, CartItem::where('product_id', $second->id)->firstOrFail()->quantity);
    }

    public function test_a_failed_reorder_leaves_the_cart_unchanged(): void
    {
        $user = $this->customer();
        $seller = $this->seller();
        $existing = $this->product($seller);
        $ordered = $this->product($seller);

        $order = $this->orderWithItems($user, [[$existing, 2], [$ordered, 3]]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $existing->id,
            'quantity'   => 1,
            'unit_price' => $existing->price,
        ]);

        // The write of the second product fails.
        Event::listen('eloquent.creating: '.CartItem::class, function (CartItem $item) use ($ordered): void {
            if ($item->product_id === $ordered->id) {
                throw new RuntimeException('cart item write failed');
            }
        });

        $this->withoutExceptionHandling();

        $thrown = null;

        try {
            $this->actingAs($user)->post(route('customer.orders.reorder', $order));
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The reorder was expected to fail.');
        $this->assertSame('cart item write failed', $thrown->getMessage());

        // Nothing was persisted: neither the increment of the first product
        // nor the insert of the second one.
        $this->assertSame(1, CartItem::count());
        $this->assertSame(1, CartItem::firstOrFail()->quantity);
        $this->assertSame($existing->id, CartItem::firstOrFail()->product_id);
    }

    public function test_reorder_keeps_the_authorization_and_the_response_contract(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $seller = $this->seller();
        $product = $this->product($seller);
        $order = $this->orderFor($owner, $product, 1);

        $this->actingAs($other)->post(route('customer.orders.reorder', $order))->assertForbidden();

        $this->assertSame(0, Cart::count());
        $this->assertSame(0, CartItem::count());

        $this->actingAs($owner)->post(route('customer.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success', 'Items added to cart.');
    }
}
