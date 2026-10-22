<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\OrderCancellationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PDOException;
use RuntimeException;
use Tests\TestCase;

/**
 * F-06.12 - bounded retry for the cancellation transaction.
 *
 * The cancellation can deadlock against a concurrent checkout, because both
 * take the same stock rows. Laravel retries a transaction when the failure is
 * a recognised concurrency error, so the cancellation asks for the same
 * bounded budget the cart mutations and the checkout use.
 *
 * The test wrapper transaction is committed in setUp, because Laravel cannot
 * retry a transaction that is nested inside another one - it raises a
 * DeadlockException instead. With the wrapper committed the cancellation is
 * the outermost transaction, exactly as it is in production, and the retry
 * really runs. The data this class creates is removed again in tearDown.
 */
class OrderCancellationRetryTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $queries = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The cancellation has to be the outermost transaction, otherwise
        // Laravel raises a DeadlockException instead of retrying.
        DB::commit();

        $this->queries = [];

        DB::listen(function ($query): void {
            $this->queries[] = strtolower(str_replace('`', '"', (string) $query->sql));
        });
    }

    protected function tearDown(): void
    {
        // The test data was committed with the wrapper transaction, so it is
        // removed explicitly to keep the rest of the suite unaffected.
        $this->truncateTestData();

        parent::tearDown();
    }

    private function truncateTestData(): void
    {
        $schema = DB::getSchemaBuilder();

        $schema->withoutForeignKeyConstraints(function () use ($schema): void {
            foreach ($schema->getTableListing() as $table) {
                if (in_array($table, ['migrations', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'], true)) {
                    continue;
                }

                DB::table($table)->delete();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * The number of times the transaction re-read and locked the order row.
     * One read per attempt.
     */
    private function attempts(): int
    {
        return count(array_filter(
            $this->queries,
            fn (string $sql) => str_starts_with($sql, 'select * from "orders" where')
        ));
    }

    private function deadlockException(): QueryException
    {
        return new QueryException(
            DB::connection()->getName(),
            'update "order_items" set "status" = ? where "id" = ?',
            [],
            new PDOException('Deadlock found when trying to get lock; try restarting transaction')
        );
    }

    /**
     * An order with one locally stocked line, a pending commission and the
     * stock it consumed.
     *
     * @return array{0: Order, 1: OrderItem, 2: Inventory, 3: Commission}
     */
    private function orderWithStock(): array
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

        $customer = User::factory()->create(['role_type' => 'customer']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => 100.00,
        ]);

        $inventory = Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 7,
        ]);

        $order = Order::create([
            'user_id'        => $customer->id,
            'subtotal'       => 100.00,
            'total'          => 100.00,
            'status'         => 'confirmed',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $item = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => 100.00,
            'subtotal'     => 100.00,
        ]);

        $commission = Commission::create([
            'order_id'      => $order->id,
            'order_item_id' => $item->id,
            'seller_id'     => $seller->id,
            'rate'          => 10.00,
            'subtotal'      => 100.00,
            'amount'        => 10.00,
            'status'        => 'pending',
        ]);

        return [$order, $item, $inventory, $commission];
    }

    /*
    |--------------------------------------------------------------------------
    | A transient deadlock is retried
    |--------------------------------------------------------------------------
    */

    public function test_a_transient_deadlock_on_the_first_attempt_is_retried_and_the_cancellation_succeeds(): void
    {
        [$order, $item, $inventory, $commission] = $this->orderWithStock();

        $writes = 0;

        Event::listen('eloquent.updating: '.Order::class, function () use (&$writes): void {
            $writes++;

            if ($writes === 1) {
                throw $this->deadlockException();
            }
        });

        $result = app(OrderCancellationService::class)->cancel($order, 'Changed my mind', null, $order->user_id);

        $this->assertTrue($result);
        $this->assertSame(2, $writes, 'The first attempt must fail and the second one must run.');
        $this->assertSame(2, $this->attempts(), 'The whole transaction must run again on the retry.');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $item->fresh()->status);
        $this->assertSame('reversed', $commission->fresh()->status);
    }

    public function test_a_retry_does_not_duplicate_the_stock_restoration_or_the_side_effects(): void
    {
        [$order, $item, $inventory, $commission] = $this->orderWithStock();

        $writes = 0;

        Event::listen('eloquent.updating: '.Order::class, function () use (&$writes): void {
            $writes++;

            if ($writes === 1) {
                throw $this->deadlockException();
            }
        });

        app(OrderCancellationService::class)->cancel($order, 'Changed my mind', null, $order->user_id);

        // The stock is restored exactly once, not once per attempt.
        $this->assertSame(8, (int) $inventory->fresh()->stock_quantity);

        // One commission reversal and one audit record.
        $this->assertSame(1, (int) Commission::where('order_item_id', $item->id)->count());
        $this->assertSame('reversed', $commission->fresh()->status);
        $this->assertSame(
            1,
            (int) AuditLog::where('action', 'financial.order_cancelled')->count(),
            'The retried cancellation must not write a second audit record.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The budget is bounded
    |--------------------------------------------------------------------------
    */

    public function test_a_deadlock_that_persists_fails_after_exactly_the_configured_attempts(): void
    {
        [$order, $item, $inventory, $commission] = $this->orderWithStock();

        $writes = 0;

        Event::listen('eloquent.updating: '.Order::class, function () use (&$writes): void {
            $writes++;

            throw $this->deadlockException();
        });

        $thrown = null;

        try {
            app(OrderCancellationService::class)->cancel($order, 'Changed my mind', null, $order->user_id);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The cancellation was expected to fail.');
        $this->assertStringContainsString('Deadlock found', $thrown->getMessage());
        $this->assertSame(
            OrderCancellationService::TRANSACTION_ATTEMPTS,
            $writes,
            'The transaction must be attempted exactly the configured number of times.'
        );
        $this->assertSame(OrderCancellationService::TRANSACTION_ATTEMPTS, $this->attempts());

        // Nothing was committed by the failed attempts.
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame('pending', $commission->fresh()->status);
        $this->assertSame(7, (int) $inventory->fresh()->stock_quantity);
    }

    /*
    |--------------------------------------------------------------------------
    | Only concurrency errors are retried
    |--------------------------------------------------------------------------
    */

    public function test_a_non_retryable_exception_is_not_retried(): void
    {
        [$order, $item, $inventory] = $this->orderWithStock();

        $writes = 0;

        Event::listen('eloquent.updating: '.Order::class, function () use (&$writes): void {
            $writes++;

            throw new RuntimeException('something else went wrong');
        });

        $thrown = null;

        try {
            app(OrderCancellationService::class)->cancel($order, 'Changed my mind', null, $order->user_id);
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The cancellation was expected to fail.');
        $this->assertSame('something else went wrong', $thrown->getMessage());
        $this->assertSame(1, $writes, 'A non-concurrency error must not be retried.');
        $this->assertSame(1, $this->attempts());
        $this->assertSame(7, (int) $inventory->fresh()->stock_quantity);
    }

    public function test_a_business_rule_failure_is_not_retried(): void
    {
        [$order, $item, $inventory] = $this->orderWithStock();

        // A paid order must be refunded, not cancelled.
        $order->update(['payment_status' => 'paid']);

        $thrown = null;

        try {
            app(OrderCancellationService::class)->cancel($order, 'Changed my mind', null, $order->user_id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The cancellation was expected to be refused.');
        $this->assertSame(1, $this->attempts(), 'A business rule failure must not be retried.');
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(7, (int) $inventory->fresh()->stock_quantity);
    }
}
