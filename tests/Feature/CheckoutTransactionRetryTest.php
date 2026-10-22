<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Jobs\ProcessOrder;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PDOException;
use Tests\TestCase;

/**
 * F-06.2 - checkout retry behaviour.
 *
 * A recognised concurrency error makes Laravel retry the checkout
 * transaction. The retry must re-read the cart and the stock, must not
 * duplicate the order, and must not duplicate the post-commit side effects.
 *
 * DatabaseMigrations is used on purpose instead of RefreshDatabase: the test
 * wrapper transaction would make Laravel turn a concurrency error into a
 * DeadlockException instead of retrying, because the checkout transaction
 * would no longer be the outermost one. Without the wrapper transaction the
 * checkout transaction is the outermost one, exactly as it is in production.
 *
 * The deadlock is simulated with the error message MySQL produces, because
 * that is what Laravel's concurrency error detector recognises.
 */
class CheckoutTransactionRetryTest extends TestCase
{
    /**
     * The schema is rebuilt for every test.
     *
     * The test database is in-memory, so it is dropped together with the
     * connection at the end of the process. The rollback that the
     * DatabaseMigrations trait performs afterwards is therefore skipped: it
     * cannot drop a column that a foreign key still references on SQLite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }

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

    private function fillCart(User $customer, Product $product, int $quantity = 1): Cart
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

    public function test_a_deadlock_retries_the_checkout_without_duplicating_the_order_or_the_side_effects(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $attempts = 0;

        // Fail the first attempt with the error MySQL raises on a deadlock.
        Event::listen('eloquent.creating: '.OrderItem::class, function () use (&$attempts): void {
            $attempts++;

            if ($attempts === 1) {
                throw new QueryException(
                    DB::connection()->getName(),
                    'insert into "order_items" ("order_id", "product_id") values (?, ?)',
                    [],
                    new PDOException('Deadlock found when trying to get lock; try restarting transaction')
                );
            }
        });

        Event::fake([OrderPlaced::class]);
        Queue::fake();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertStatus(302);

        // The first attempt was rolled back and the second one committed.
        $this->assertSame(2, $attempts, 'The checkout transaction must have been retried once.');
        $this->assertSame(1, Order::count(), 'The retry must not create a second order.');
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(2, OrderItem::firstOrFail()->quantity);

        // The stock movement of the rolled back attempt must not survive.
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(0, CartItem::count());

        // The side effects run once, after the successful attempt.
        Event::assertDispatched(OrderPlaced::class, 1);
        Queue::assertPushed(ProcessOrder::class, 1);
    }

    public function test_a_failure_that_is_not_a_concurrency_error_is_not_retried(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 1);

        // More than the available stock, so the checkout fails.
        $this->fillCart($customer, $product, 5);

        $attempts = 0;

        Event::listen('eloquent.creating: '.OrderItem::class, function () use (&$attempts): void {
            $attempts++;
        });

        Event::fake([OrderPlaced::class]);
        Queue::fake();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertSessionHasErrors('cart');

        $this->assertSame(1, $attempts, 'A stock failure must not be retried.');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(1, CartItem::count());
        $this->assertSame(1, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        Event::assertNotDispatched(OrderPlaced::class);
        Queue::assertNotPushed(ProcessOrder::class);
    }
}
