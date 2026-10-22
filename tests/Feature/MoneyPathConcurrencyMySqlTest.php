<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CodCollection;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProcurementOrder;
use App\Models\ProcurementOrderItem;
use App\Models\SellerBalance;
use App\Models\SellerProfile;
use App\Models\SupplierBalance;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * R3.2 - real-engine verification of the money path concurrency behaviour.
 *
 * The settlement and the COD collection are guarded by row locks and by a
 * status guard, and both of those need a real MySQL/InnoDB server: SQLite
 * compiles lockForUpdate() to an empty string and has no row-level locking,
 * so it cannot show whether two concurrent workers really settle once.
 *
 * These tests are therefore gated on an explicitly configured disposable test
 * database, exactly like the cart concurrency harness:
 *
 *   MYSQL_TEST_DB_HOST      (default 127.0.0.1)
 *   MYSQL_TEST_DB_PORT      (default 3306)
 *   MYSQL_TEST_DB_DATABASE  (must contain "test")
 *   MYSQL_TEST_DB_USERNAME
 *   MYSQL_TEST_DB_PASSWORD
 *
 * The database must be disposable: its schema is rebuilt for every test in
 * this class. The name has to contain "test" so a production or a developer
 * database can never be rebuilt by accident.
 *
 * Each race uses two independent worker processes, so the two operations hold
 * separate database connections and genuinely overlap.
 */
class MoneyPathConcurrencyMySqlTest extends TestCase
{
    /** @var array<int, resource> */
    private array $processes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireDisposableMySqlDatabase();
    }

    protected function tearDown(): void
    {
        foreach ($this->processes as $process) {
            if (is_resource($process)) {
                proc_close($process);
            }
        }

        $this->processes = [];

        parent::tearDown();
    }

    /**
     * Point the application at the configured disposable test database.
     */
    private function requireDisposableMySqlDatabase(): void
    {
        $host = env('MYSQL_TEST_DB_HOST');
        $database = env('MYSQL_TEST_DB_DATABASE');

        if (! $host || ! $database) {
            $this->markTestSkipped(
                'Set MYSQL_TEST_DB_HOST, MYSQL_TEST_DB_PORT, MYSQL_TEST_DB_DATABASE, MYSQL_TEST_DB_USERNAME '
                .'and MYSQL_TEST_DB_PASSWORD to run the real-engine money path concurrency tests. They need a '
                .'disposable MySQL/InnoDB database and must never be pointed at production.'
            );
        }

        if (! str_contains(strtolower((string) $database), 'test')) {
            $this->markTestSkipped(
                'The configured MYSQL_TEST_DB_DATABASE name must contain "test": this class rebuilds the schema.'
            );
        }

        $settings = [
            'DB_CONNECTION'            => 'mysql',
            'DB_HOST'                  => $host,
            'DB_PORT'                  => (string) env('MYSQL_TEST_DB_PORT', '3306'),
            'DB_DATABASE'              => $database,
            'DB_USERNAME'              => (string) env('MYSQL_TEST_DB_USERNAME', 'root'),
            'DB_PASSWORD'              => (string) env('MYSQL_TEST_DB_PASSWORD', ''),
            'SESSION_DRIVER'           => 'array',
        ];

        // The worker processes inherit the environment, so the application
        // resolves the same database in the test and in the workers.
        foreach ($settings as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(config('database.connections.mysql'), [
                'host'     => $settings['DB_HOST'],
                'port'     => $settings['DB_PORT'],
                'database' => $settings['DB_DATABASE'],
                'username' => $settings['DB_USERNAME'],
                'password' => $settings['DB_PASSWORD'],
            ]),
        ]);

        DB::purge('mysql');

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The configured test database is not a MySQL connection.');
        }

        // The schema of the disposable database is rebuilt for every test.
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function seller(float $balance = 100.00): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

        SellerBalance::create([
            'seller_id'       => $seller->id,
            'balance'         => $balance,
            'total_earned'    => $balance,
            'total_withdrawn' => 0,
        ]);

        return $seller;
    }

    private function supplier(float $balance = 0.00): User
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);

        SupplierBalance::create([
            'supplier_id'     => $supplier->id,
            'balance'         => $balance,
            'pending_balance' => 0,
            'total_earned'    => $balance,
            'total_withdrawn' => 0,
            'platform_fees'   => 0,
        ]);

        return $supplier;
    }

    /**
     * A procurement order whose order line is already delivered, so the
     * synchronisation settles the supplier.
     */
    private function procurementFor(User $supplier, User $seller, float $subtotal = 120.00, string $poStatus = 'shipped'): ProcurementOrder
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $subtotal,
        ]);

        $order = Order::create([
            'user_id'  => $seller->id,
            'subtotal' => $subtotal,
            'total'    => $subtotal,
            'status'   => 'confirmed',
        ]);

        $item = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => $subtotal,
            'subtotal'     => $subtotal,
            'status'       => 'delivered',
        ]);

        $po = ProcurementOrder::create([
            'order_id'    => $order->id,
            'supplier_id' => $supplier->id,
            'seller_id'   => $seller->id,
            'status'      => $poStatus,
            'subtotal'    => $subtotal,
        ]);

        ProcurementOrderItem::create([
            'procurement_order_id' => $po->id,
            'order_item_id'        => $item->id,
            'product_id'           => $product->id,
            'product_name'         => $product->name,
            'quantity'             => 1,
            'unit_cost'            => 10.00,
            'subtotal'             => $subtotal,
            'status'               => 'delivered',
        ]);

        return $po;
    }

    private function pendingCodCollection(User $seller, float $expected = 80.00): CodCollection
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $expected,
        ]);

        $order = Order::create([
            'user_id'        => $seller->id,
            'subtotal'       => $expected,
            'total'          => $expected,
            'status'         => 'confirmed',
            'payment_method' => 'cod',
        ]);

        $item = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => $expected,
            'subtotal'     => $expected,
            'status'       => 'pending',
        ]);

        return CodCollection::create([
            'order_item_id'    => $item->id,
            'order_id'         => $order->id,
            'collector_id'     => $seller->id,
            'collector_role'   => 'seller',
            'expected_amount'  => $expected,
            'collected_amount' => 0,
            'shortfall_amount' => 0,
            'overpaid_amount'  => 0,
            'status'           => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | The checkout races
    |--------------------------------------------------------------------------
    |
    | The three races below close the concurrency gaps the R3.12-F audit
    | recorded: the same cart checked out twice, two buyers competing for the
    | last locally stocked unit, and a web checkout racing an API checkout
    | over the same inventory. Every one of them drives the real checkout
    | endpoints from two independent worker processes, so the two requests
    | hold separate database connections and genuinely overlap.
    |
    */

    public function test_two_concurrent_checkouts_of_the_same_cart_create_exactly_one_order(): void
    {
        $customer = $this->customer();
        $seller = $this->seller();
        $address = $this->addressFor($customer);
        $product = $this->productWithInventory($seller, 25.00, 5);
        $cart = $this->cartFor($customer, $product, 1);

        $results = $this->runConcurrently([
            $this->webCheckoutWorker(1, $customer, $address),
            $this->webCheckoutWorker(2, $customer, $address),
        ]);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
        }

        // Exactly one order may exist: the second checkout waits for the cart
        // row lock and then finds the cart the first request consumed.
        $this->assertSame(1, Order::where('user_id', $customer->id)->count(), 'Exactly one order may be created.');

        $order = Order::where('user_id', $customer->id)->firstOrFail();

        $this->assertSame(1, $order->items()->count(), 'The order must hold the cart items exactly once.');
        $this->assertSame(1, CodCollection::where('order_id', $order->id)->count(), 'The COD collection must exist exactly once.');

        // The stock moved once, not twice.
        $this->assertSame(4, (int) Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        // A cash on delivery order is credited when the cash is collected, not
        // when the order is placed, so no seller credit may exist yet.
        $this->assertSame(0, Transaction::count(), 'The checkout must not credit a seller for an uncollected order.');

        // The cart the winner consumed is empty.
        $this->assertSame(0, CartItem::where('cart_id', $cart->id)->count(), 'The cart must be empty after the checkout.');

        // The losing request must have been told the cart is empty, and it
        // must not have produced a server error.
        [$winner, $loser] = $this->splitWinnerAndLoser($results, (int) $order->id);

        $this->assertNotSame(500, (int) $loser['status'], 'The losing checkout must not produce a server error.');
        $this->assertContains(
            (int) $loser['status'],
            [302, 404, 422],
            'The losing checkout must end in a controlled outcome, got: '.$loser['status'].' '.$loser['location']
        );
        $this->assertStringContainsString(
            'Your cart is empty',
            implode(' ', (array) ($loser['messages'] ?? [])),
            'The losing checkout must report the empty cart, got: '.json_encode($loser['messages'] ?? []).' session='.json_encode($loser['session_keys'] ?? []).' errors_type='.json_encode($loser['errors_type'] ?? null).' errors_keys='.json_encode($loser['errors_keys'] ?? null).' sub='.json_encode($loser['errors_sub_keys'] ?? null).' flash_new='.json_encode($loser['flash_new_keys'] ?? [])
        );

        $this->assertNotSame($winner['worker'], $loser['worker']);
        $this->assertSame((int) $order->id, (int) $winner['created_order_id']);
    }

    public function test_two_concurrent_checkouts_of_the_last_inventory_unit_produce_one_order(): void
    {
        $seller = $this->seller();
        $product = $this->productWithInventory($seller, 25.00, 1);

        $firstBuyer = $this->customer();
        $secondBuyer = $this->customer();

        $firstAddress = $this->addressFor($firstBuyer);
        $secondAddress = $this->addressFor($secondBuyer);

        $firstCart = $this->cartFor($firstBuyer, $product, 1);
        $secondCart = $this->cartFor($secondBuyer, $product, 1);

        $results = $this->runConcurrently([
            $this->webCheckoutWorker(1, $firstBuyer, $firstAddress),
            $this->webCheckoutWorker(2, $secondBuyer, $secondAddress),
        ]);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
        }

        // Only one of the two buyers may take the last unit.
        $this->assertSame(1, Order::count(), 'Exactly one order may be created.');

        $inventory = Inventory::where('product_id', $product->id)->firstOrFail();

        $this->assertSame(0, (int) $inventory->stock_quantity, 'The last unit must be consumed exactly once.');
        $this->assertGreaterThanOrEqual(0, (int) $inventory->stock_quantity, 'The inventory must never become negative.');

        $this->assertSame(1, OrderItem::count(), 'The order items must not be duplicated.');
        $this->assertSame(1, CodCollection::count(), 'The COD collection must not be duplicated.');
        $this->assertSame(0, Transaction::count(), 'The checkout must not credit a seller for an uncollected order.');

        // The cart of the buyer that lost keeps its item: the cart is only
        // cleared after the stock has been reserved.
        $emptyCarts = CartItem::whereIn('cart_id', [$firstCart->id, $secondCart->id])->count();

        $this->assertSame(1, $emptyCarts, 'The losing cart must keep its item.');

        $loser = $this->loserResult($results);

        $this->assertNotSame(
            500,
            (int) $loser['status'],
            'The losing checkout must not produce a server error. The worker reported: '
            .json_encode($loser['error'] ?? null)
            .' db_failures='.json_encode($loser['db_failures'] ?? [])
            .' status='.json_encode($loser['status'] ?? null)
            .' location='.json_encode($loser['location'] ?? null)
            .' body='.json_encode(substr((string) ($loser['body'] ?? ''), 0, 300))
        );
        $this->assertContains(
            (int) $loser['status'],
            [302, 409, 422],
            'The losing checkout must end in a controlled outcome, got: '.$loser['status'].' '.$loser['location']
        );
        $this->assertStringContainsString(
            'Insufficient stock',
            implode(' ', (array) ($loser['messages'] ?? [])),
            'The losing checkout must report the missing stock, got: '.json_encode($loser['messages'] ?? []).' session='.json_encode($loser['session_keys'] ?? []).' errors_type='.json_encode($loser['errors_type'] ?? null).' errors_keys='.json_encode($loser['errors_keys'] ?? null).' sub='.json_encode($loser['errors_sub_keys'] ?? null).' flash_new='.json_encode($loser['flash_new_keys'] ?? [])
        );
    }

    public function test_a_web_checkout_racing_an_api_checkout_conserves_inventory(): void
    {
        $seller = $this->seller();
        $supplier = $this->supplier();

        // A sourced product: the checkout reserves the supplier source stock
        // instead of the local inventory, so the race is observed on the
        // source stock.
        $product = $this->sourcedProductWithStock($supplier, $seller, 25.00, 1);

        $webBuyer = $this->customer();
        $apiBuyer = $this->customer();

        $webAddress = $this->addressFor($webBuyer);
        $apiAddress = $this->addressFor($apiBuyer);

        $this->cartFor($webBuyer, $product, 1);
        $this->cartFor($apiBuyer, $product, 1);

        $results = $this->runConcurrently([
            $this->webCheckoutWorker(1, $webBuyer, $webAddress),
            [
                'worker'   => 2,
                'operation' => 'checkout_api',
                'user_id'  => $apiBuyer->id,
                'uri'      => '/api/v1/checkout',
                'input'    => [
                    'shipping_address_id' => $apiAddress->id,
                    'billing_address_id'  => $apiAddress->id,
                    'payment_method'      => 'cod',
                ],
            ],
        ]);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
        }

        $this->assertSame(1, Order::count(), 'Exactly one order may be created.');

        $order = Order::firstOrFail();

        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, CodCollection::where('order_id', $order->id)->count(), 'The COD collection must not be duplicated.');
        $this->assertSame(0, Transaction::count(), 'The checkout must not credit a seller for an uncollected order.');

        // The supplier source stock is reserved exactly once and the physical
        // stock is untouched.
        $stock = SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)->firstOrFail();

        $this->assertSame(1, (int) $stock->reserved_quantity, 'The source stock must be reserved exactly once.');
        $this->assertSame(1, (int) $stock->stock_quantity, 'The physical source stock must not change.');

        // A sourced checkout creates one procurement order for the winner.
        $this->assertSame(1, ProcurementOrder::where('order_id', $order->id)->count(), 'The procurement order must exist exactly once.');

        $loser = $this->loserResult($results);

        $this->assertNotSame(500, (int) $loser['status'], 'The losing checkout must not produce a server error.');
        $this->assertContains(
            (int) $loser['status'],
            [302, 404, 409, 422],
            'The losing checkout must end in a controlled outcome, got: '.$loser['status'].' '.$loser['body']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The checkout fixtures
    |--------------------------------------------------------------------------
    */

    /**
     * A web checkout worker for the given customer.
     *
     * @return array<string, mixed>
     */
    private function webCheckoutWorker(int $worker, User $customer, Address $address): array
    {
        return [
            'worker'    => $worker,
            'operation' => 'checkout_web',
            'user_id'   => $customer->id,
            'uri'       => '/checkout',
            'input'     => [
                'shipping_address_id' => $address->id,
                'payment_method'      => 'cod',
                'billing_same'        => '1',
            ],
        ];
    }

    /**
     * The result of the worker that did not create the order.
     *
     * The worker identifies the order it created from its own response, so
     * the worker without an order id in its response is the one that lost the
     * race.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function loserResult(array $results): array
    {
        foreach ($results as $result) {
            if (! isset($result['created_order_id'])) {
                return $result;
            }
        }

        $this->fail('Both workers reported an order; the race did not behave as expected.');
    }

    /**
     * Split the results into the worker that created the order and the one
     * that did not.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function splitWinnerAndLoser(array $results, int $orderId): array
    {
        $winner = null;
        $loser = null;

        foreach ($results as $result) {
            if (isset($result['created_order_id']) && (int) $result['created_order_id'] === $orderId) {
                $winner = $result;
            } else {
                $loser = $result;
            }
        }

        $this->assertNotNull($winner, 'The worker that created the order must report it.');
        $this->assertNotNull($loser, 'The worker that lost the race must report its outcome.');

        return [$winner, $loser];
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
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);
    }

    /**
     * A locally stocked product with its own inventory row.
     */
    private function productWithInventory(User $seller, float $price, int $stock): Product
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => $price,
            'status'    => 'published',
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => $stock,
        ]);

        return $product;
    }

    /**
     * A sourced product: the checkout reserves the supplier source stock
     * instead of the local inventory.
     */
    private function sourcedProductWithStock(User $supplier, User $seller, float $price, int $stock): Product
    {
        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'wholesale_price' => 10.00,
            'status'          => 'published',
        ]);

        SupplierProductStock::create([
            'supplier_product_id' => $supplierProduct->id,
            'stock_quantity'      => $stock,
            'reserved_quantity'   => 0,
            'sold_quantity'       => 0,
        ]);

        return Product::factory()->create([
            'seller_id'           => $seller->id,
            'price'               => $price,
            'status'              => 'published',
            'supplier_product_id' => $supplierProduct->id,
        ]);
    }

    /**
     * A cart that holds the given quantity of the product.
     */
    private function cartFor(User $customer, Product $product, int $quantity): Cart
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

    /**
     * Run the given workers at the same time and collect their results.
     *
     * @param  array<int, array<string, mixed>>  $workers
     * @return array<int, array<string, mixed>>
     */
    private function runConcurrently(array $workers): array
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'money_concurrency_'.bin2hex(random_bytes(6));

        mkdir($barrier, 0777, true);

        foreach ($workers as $worker) {
            $payloadFile = $barrier.'/payload.'.$worker['worker'].'.json';
            file_put_contents($payloadFile, (string) json_encode($worker));

            $command = escapeshellarg(PHP_BINARY)
                .' '.escapeshellarg(base_path('tests/Support/MoneyPathConcurrencyWorker.php'))
                .' '.escapeshellarg($payloadFile)
                .' '.escapeshellarg($barrier);

            $descriptors = [
                1 => ['file', $barrier.'/stdout.'.$worker['worker'].'.txt', 'w'],
                2 => ['file', $barrier.'/stderr.'.$worker['worker'].'.txt', 'w'],
            ];

            $process = proc_open($command, $descriptors, $pipes, base_path());

            if (! is_resource($process)) {
                $this->fail('The worker process could not be started.');
            }

            $this->processes[] = $process;
        }

        $this->waitFor($barrier, 'ready.', count($workers));

        // Release both workers at the same moment.
        file_put_contents($barrier.'/go', '1');

        $this->waitFor($barrier, 'result.', count($workers));

        $results = [];

        foreach (glob($barrier.'/result.*') ?: [] as $file) {
            $results[] = json_decode((string) file_get_contents($file), true);
        }

        usort($results, fn ($a, $b) => $a['worker'] <=> $b['worker']);

        foreach (glob($barrier.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($barrier);

        return $results;
    }

    private function waitFor(string $barrier, string $prefix, int $expected): void
    {
        $deadline = microtime(true) + 60;

        while (count(glob($barrier.'/'.$prefix.'*') ?: []) < $expected) {
            usleep(5000);

            if (microtime(true) > $deadline) {
                $this->fail("The workers did not produce {$prefix}* files in time.");
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Concurrent settlement
    |--------------------------------------------------------------------------
    */

    public function test_two_concurrent_settlements_credit_the_supplier_once(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->procurementFor($supplier, $seller);

        $results = $this->runConcurrently([
            [
                'worker'             => 1,
                'operation'          => 'settle',
                'procurement_order_id' => $po->id,
            ],
            [
                'worker'             => 2,
                'operation'          => 'settle',
                'procurement_order_id' => $po->id,
            ],
        ]);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
        }

        $this->assertNotNull($po->fresh()->settled_at);
        $this->assertSame(
            120.00,
            (float) SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->balance,
            'The supplier must be credited exactly once.'
        );
        $this->assertSame(
            1,
            (int) Transaction::where('type', 'credit')->count(),
            'The supplier must have exactly one settlement ledger entry.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Concurrent reconciliation
    |--------------------------------------------------------------------------
    */

    public function test_two_concurrent_reconciliations_credit_the_supplier_once(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();

        // The state a crash between the synchronisation commit and the
        // settlement commit leaves behind: delivered, but never settled.
        $po = $this->procurementFor($supplier, $seller, 120.00, 'delivered');

        $results = $this->runConcurrently([
            [
                'worker'               => 1,
                'operation'            => 'reconcile',
                'procurement_order_id' => $po->id,
            ],
            [
                'worker'               => 2,
                'operation'            => 'reconcile',
                'procurement_order_id' => $po->id,
            ],
        ]);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
        }

        $this->assertNotNull($po->fresh()->settled_at);
        $this->assertSame(
            120.00,
            (float) SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->balance,
            'The supplier must be credited exactly once.'
        );
        $this->assertSame(
            1,
            (int) Transaction::where('type', 'credit')->count(),
            'The supplier must have exactly one settlement ledger entry.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Concurrent COD collection
    |--------------------------------------------------------------------------
    */

    public function test_two_concurrent_cod_collections_credit_the_seller_once(): void
    {
        $seller = $this->seller();
        $collection = $this->pendingCodCollection($seller);

        $results = $this->runConcurrently([
            [
                'worker'      => 1,
                'operation'   => 'cod_collection',
                'collection_id' => $collection->id,
                'amount'      => 80.00,
                'collected_by' => $seller->id,
            ],
            [
                'worker'      => 2,
                'operation'   => 'cod_collection',
                'collection_id' => $collection->id,
                'amount'      => 80.00,
                'collected_by' => $seller->id,
            ],
        ]);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
        }

        $this->assertSame('collected', $collection->fresh()->status);
        $this->assertSame(
            172.00,
            (float) SellerBalance::where('seller_id', $seller->id)->firstOrFail()->balance,
            'The seller must be credited exactly once: 100.00 of balance plus the 72.00 of the collection.'
        );
        $this->assertSame(
            1,
            (int) Transaction::where('type', 'credit')->count(),
            'The seller must have exactly one collection ledger entry.'
        );
    }
}
