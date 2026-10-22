<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\TransactionRetry;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * R3.12-F - the real InnoDB deadlock behaviour of the checkout transaction.
 *
 * The retry tests that exist so far inject a synthetic exception, which proves
 * that the retry mechanism runs but not that a genuine InnoDB deadlock is
 * detected, classified and retried. These tests close that gap on a real
 * MySQL/InnoDB server.
 *
 * They are gated on an explicitly configured disposable test database, exactly
 * like the other concurrency harnesses:
 *
 *   MYSQL_TEST_DB_HOST      (default 127.0.0.1)
 *   MYSQL_TEST_DB_PORT      (default 3306)
 *   MYSQL_TEST_DB_DATABASE  (must contain "test")
 *   MYSQL_TEST_DB_USERNAME
 *   MYSQL_TEST_DB_PASSWORD
 *
 * The database must be disposable: its schema is rebuilt for every test in
 * this class, and the name has to contain "test" so a production or a
 * developer database can never be rebuilt by accident.
 *
 * The deadlock is created with two independent connections - this process and
 * a worker process - that hold one row each and then ask for the other one.
 * InnoDB detects the cycle and rolls one side back with error 1213 (SQLSTATE
 * 40001), which is a different error from a lock wait timeout (1205, SQLSTATE
 * HY000); the two are told apart by the driver error code, never by the
 * message.
 */
class MySqlDeadlockTest extends TestCase
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
                .'and MYSQL_TEST_DB_PASSWORD to run the real-engine deadlock tests. They need a '
                .'disposable MySQL/InnoDB database and must never be pointed at production.'
            );
        }

        if (! str_contains(strtolower((string) $database), 'test')) {
            $this->markTestSkipped(
                'The configured MYSQL_TEST_DB_DATABASE name must contain "test": this class rebuilds the schema.'
            );
        }

        $settings = [
            'DB_CONNECTION'           => 'mysql',
            'DB_HOST'                 => $host,
            'DB_PORT'                 => (string) env('MYSQL_TEST_DB_PORT', '3306'),
            'DB_DATABASE'             => $database,
            'DB_USERNAME'             => (string) env('MYSQL_TEST_DB_USERNAME', 'root'),
            'DB_PASSWORD'             => (string) env('MYSQL_TEST_DB_PASSWORD', ''),
            'SESSION_DRIVER'          => 'array',
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
    | A real deadlock
    |--------------------------------------------------------------------------
    */

    public function test_innodb_reports_error_1213_on_a_real_deadlock(): void
    {
        $first = $this->inventoryRow();
        $second = $this->inventoryRow();

        // This connection holds the first row and then asks for the second one.
        DB::statement('START TRANSACTION');
        DB::select('SELECT id FROM `inventory` WHERE id = '.$first.' FOR UPDATE');

        $barrier = $this->barrier();

        try {
            // The worker holds the second row and then asks for the first one.
            $this->spawnDeadlockWorker($barrier, [
                'worker'     => 1,
                'operation'  => 'deadlock_hold',
                'lock_table' => 'inventory',
                'lock_id'    => $second,
                'wait_table' => 'inventory',
                'wait_id'    => $first,
            ]);

            // The worker waits for the barrier before it takes its locks.
            $this->waitFor($barrier, 'ready.', 1);
            file_put_contents($barrier.'/go', '1');

            // Wait until the worker holds its row, so the cycle is complete.
            $this->waitFor($barrier, 'holding.', 1);

            $observed = null;

            try {
                DB::select('SELECT id FROM `inventory` WHERE id = '.$second.' FOR UPDATE');
            } catch (QueryException $e) {
                $observed = $this->errorFacts($e);
            }

            $worker = $this->collectWorkerResult($barrier, 1);

            $this->assertNotNull(
                $observed['code'] ?? ($worker['driver_code'] ?? null),
                'InnoDB must have resolved the cycle with a deadlock error.'
            );

            // The deadlock is 1213 with SQLSTATE 40001. A lock wait timeout
            // (1205, HY000) is a different failure and must never be reported
            // as a deadlock.
            $codes = array_filter([
                $observed['code'] ?? null,
                $worker['driver_code'] ?? null,
            ]);

            $this->assertContains(1213, $codes, 'A real deadlock must report the driver error code 1213.');

            foreach ($codes as $code) {
                $this->assertNotSame(1205, $code, 'A lock wait timeout must not be reported as a deadlock.');
            }

            $states = array_filter([
                $observed['sqlstate'] ?? null,
                $worker['sqlstate'] ?? null,
            ]);

            foreach ($states as $state) {
                $this->assertNotSame('HY000', $state, 'A lock wait timeout reports SQLSTATE HY000, not 40001.');
            }
        } finally {
            DB::statement('ROLLBACK');
            $this->cleanBarrier($barrier);
        }
    }

    public function test_a_lock_wait_timeout_is_distinguishable_from_a_deadlock(): void
    {
        $row = $this->inventoryRow();

        // This connection holds the row.
        DB::statement('START TRANSACTION');
        DB::select('SELECT id FROM `inventory` WHERE id = '.$row.' FOR UPDATE');

        try {
            // A second connection that waits for the row and gives up after one
            // second: that is a lock wait timeout (1205), not a deadlock.
            $waiter = DB::connection()->getPdo();

            $connection = new \PDO(
                'mysql:host='.env('MYSQL_TEST_DB_HOST').';port='.env('MYSQL_TEST_DB_PORT', '3306').';dbname='.env('MYSQL_TEST_DB_DATABASE'),
                (string) env('MYSQL_TEST_DB_USERNAME', 'root'),
                (string) env('MYSQL_TEST_DB_PASSWORD', ''),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $connection->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $facts = null;

            try {
                $connection->query('SELECT id FROM `inventory` WHERE id = '.$row.' FOR UPDATE');
            } catch (\PDOException $e) {
                $facts = $e->errorInfo;
            }

            $this->assertNotNull($facts, 'The waiting connection must have failed.');
            $this->assertSame(1205, (int) $facts[1], 'A lock wait timeout reports the driver error code 1205.');
            $this->assertSame('HY000', (string) $facts[0], 'A lock wait timeout reports SQLSTATE HY000.');
            $this->assertNotSame(40001, (string) $facts[0], 'A lock wait timeout is not the deadlock SQLSTATE.');
        } finally {
            DB::statement('ROLLBACK');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | The checkout retry against a real deadlock
    |--------------------------------------------------------------------------
    */

    public function test_the_checkout_retries_a_real_1213_and_creates_one_order(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $customer = User::factory()->create(['role_type' => 'customer']);

        $address = \App\Models\Address::create([
            'user_id'       => $customer->id,
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

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 25.00,
            'status'    => 'published',
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 5,
        ]);

        $cart = Cart::create(['user_id' => $customer->id]);

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        // This connection holds the inventory row the checkout needs, plus a
        // number of spare rows. InnoDB rolls the cheaper transaction back when
        // it resolves a deadlock, and the checkout transaction holds fewer
        // record locks than this one, so the checkout becomes the victim.
        $spareRows = [];

        DB::statement('START TRANSACTION');
        DB::select('SELECT id FROM `inventory` WHERE id = '.(int) Inventory::where('product_id', $product->id)->firstOrFail()->id.' FOR UPDATE');

        for ($i = 0; $i < 60; $i++) {
            $spareRows[] = $this->inventoryRow();
        }

        foreach ($spareRows as $spare) {
            DB::select('SELECT id FROM `inventory` WHERE id = '.$spare.' FOR UPDATE');
        }

        $barrier = $this->barrier();

        try {
            $this->spawnCheckoutWorker($barrier, [
                'worker'    => 1,
                'operation' => 'checkout_web',
                'user_id'   => $customer->id,
                'uri'       => '/checkout',
                'input'     => [
                    'shipping_address_id' => $address->id,
                    'payment_method'      => 'cod',
                    'billing_same'        => '1',
                ],
            ]);

            // The worker waits for the barrier before it starts the checkout.
            $this->waitFor($barrier, 'ready.', 1);

            // The transactions that exist before the checkout starts. A wait of
            // one of them is not the checkout.
            $knownTransactions = $this->knownTransactionIds();

            file_put_contents($barrier.'/go', '1');

            // Wait until the checkout transaction holds the cart row and waits
            // for the inventory row this connection holds. The observation is
            // read-only, so it cannot take the contested cart lock itself.
            $this->waitForInventoryLockWait($knownTransactions);

            $observed = null;

            try {
                // The cycle: the checkout waits for the inventory row this
                // connection holds, and this connection now waits for the cart
                // row the checkout holds.
                DB::select('SELECT id FROM `carts` WHERE id = '.(int) $cart->id.' FOR UPDATE');
            } catch (QueryException $e) {
                $observed = $this->errorFacts($e);
            }

            // The checkout was rolled back by the deadlock, so it retries. The
            // retry needs the inventory row, which this connection still
            // holds, so it is released now.
            DB::statement('ROLLBACK');

            $worker = $this->collectWorkerResult($barrier, 1);

            // The deadlock the application ran into. The application retries a
            // transaction that failed with a concurrency error, so the error
            // itself never reaches the response: it is recorded on the
            // connection of the worker.
            $deadlocks = array_values(array_filter(
                (array) ($worker['db_failures'] ?? []),
                fn ($failure) => (int) ($failure['code'] ?? 0) === 1213
            ));

            $this->assertNotEmpty(
                $deadlocks,
                'The checkout must have hit a real InnoDB deadlock (1213). The worker reported: '
                .json_encode($worker['db_failures'] ?? [])
                .' and this side saw: '.json_encode($observed)
            );

            $this->assertSame(
                '40001',
                (string) ($deadlocks[0]['sqlstate'] ?? ''),
                'A real deadlock reports SQLSTATE 40001, not the HY000 of a lock wait timeout.'
            );

            $this->assertLessThanOrEqual(
                TransactionRetry::ATTEMPTS,
                count($deadlocks),
                'The retry must stay inside the attempt budget of the application.'
            );

            // The deadlock itself, as InnoDB recorded it. The two rows of the
            // cycle are the cart row the checkout holds and the inventory row
            // this connection holds.
            $report = $this->latestDeadlockReport();

            $this->assertStringContainsString(
                'carts',
                $report,
                'InnoDB must have recorded a deadlock on the cart row the checkout holds.'
            );

            $this->assertStringContainsString(
                'inventory',
                $report,
                'InnoDB must have recorded a deadlock on the inventory row the checkout waits for.'
            );

            // The retry succeeded, so the checkout answered with the order it
            // created.
            $this->assertSame(
                302,
                (int) ($worker['status'] ?? 0),
                'The retried checkout must succeed. The worker reported: '
                .json_encode($worker['status'] ?? null).' '.json_encode($worker['location'] ?? null)
                .' '.json_encode($worker['messages'] ?? [])
            );

            $this->assertNotNull(
                $worker['created_order_id'] ?? null,
                'The retried checkout must answer with the order it created.'
            );

            $this->assertSame(1, Order::count(), 'The retried checkout must create exactly one order.');
            $this->assertSame(1, OrderItem::count(), 'The retried checkout must create the order items once.');
            $this->assertSame(
                4,
                (int) Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity,
                'The retried checkout must consume the stock exactly once.'
            );
            $this->assertSame(0, CartItem::where('cart_id', $cart->id)->count(), 'The retried checkout must empty the cart.');
        } finally {
            DB::statement('ROLLBACK');
            $this->cleanBarrier($barrier);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * An inventory row the deadlock tests can lock.
     */
    private function inventoryRow(): int
    {
        $product = Product::factory()->create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'price'     => 10.00,
            'status'    => 'published',
        ]);

        $inventory = Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);

        return (int) $inventory->id;
    }

    /**
     * The driver error code and the SQLSTATE of a database error.
     *
     * @return array{code: int|null, sqlstate: string|null}
     */
    private function errorFacts(QueryException $e): array
    {
        return [
            'code'     => isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : null,
            'sqlstate' => isset($e->errorInfo[0]) ? (string) $e->errorInfo[0] : null,
        ];
    }

    private function barrier(): string
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mysql_deadlock_'.bin2hex(random_bytes(6));

        mkdir($barrier, 0777, true);

        return $barrier;
    }

    private function cleanBarrier(string $barrier): void
    {
        foreach (glob($barrier.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($barrier);
    }

    /**
     * Start a worker that holds a row and waits for another one.
     *
     * @param  array<string, mixed>  $payload
     */
    private function spawnDeadlockWorker(string $barrier, array $payload): void
    {
        $payloadFile = $barrier.'/payload.'.$payload['worker'].'.json';
        file_put_contents($payloadFile, (string) json_encode($payload));

        $this->spawnWorker($barrier, $payloadFile);
    }

    /**
     * Start a worker that performs a checkout.
     *
     * @param  array<string, mixed>  $payload
     */
    private function spawnCheckoutWorker(string $barrier, array $payload): void
    {
        $payloadFile = $barrier.'/payload.'.$payload['worker'].'.json';
        file_put_contents($payloadFile, (string) json_encode($payload));

        $this->spawnWorker($barrier, $payloadFile);
    }

    private function spawnWorker(string $barrier, string $payloadFile): void
    {
        $command = escapeshellarg(PHP_BINARY)
            .' '.escapeshellarg(base_path('tests/Support/MoneyPathConcurrencyWorker.php'))
            .' '.escapeshellarg($payloadFile)
            .' '.escapeshellarg($barrier);

        $descriptors = [
            1 => ['file', $barrier.'/stdout.'.basename($payloadFile, '.json').'.txt', 'w'],
            2 => ['file', $barrier.'/stderr.'.basename($payloadFile, '.json').'.txt', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, base_path());

        if (! is_resource($process)) {
            $this->fail('The worker process could not be started.');
        }

        $this->processes[] = $process;
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

    /**
     * Wait until the checkout transaction waits for the inventory row.
     *
     * The lock state is observed through the Performance Schema, which is a
     * read-only view: the observer never locks the contested cart row, so it
     * cannot change the choreography it is waiting for.
     *
     * The Performance Schema is global, so a wait left over from an earlier
     * test would be seen here as well. The wait is therefore only accepted
     * when it belongs to a transaction that did not exist before the checkout
     * was started, and when the transaction it is blocked by is this
     * connection, which holds the inventory row.
     *
     * @param  array<int, string>  $knownTransactions
     */
    private function waitForInventoryLockWait(array $knownTransactions): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        $deadline = microtime(true) + 60;

        while (microtime(true) < $deadline) {
            $waits = DB::select(
                'SELECT w.REQUESTING_ENGINE_TRANSACTION_ID AS requesting,
                        w.BLOCKING_ENGINE_TRANSACTION_ID AS blocking
                 FROM performance_schema.data_lock_waits w
                 JOIN performance_schema.data_locks l
                   ON l.ENGINE_LOCK_ID = w.REQUESTING_ENGINE_LOCK_ID
                 WHERE l.OBJECT_SCHEMA = ? AND l.OBJECT_NAME = \'inventory\'',
                [$database]
            );

            foreach ($waits as $wait) {
                $requesting = (string) $wait->requesting;

                if (in_array($requesting, $knownTransactions, true)) {
                    // A wait of an earlier transaction: not our checkout.
                    continue;
                }

                if ((string) $wait->blocking !== $this->transactionId()) {
                    // The wait is not caused by the inventory row this
                    // connection holds.
                    continue;
                }

                return;
            }

            usleep(2000);
        }

        $this->fail('The checkout transaction did not wait for the inventory row in time.');
    }

    /**
     * The InnoDB transaction id of this connection.
     */
    private function transactionId(): string
    {
        $row = DB::selectOne(
            'SELECT trx_id FROM information_schema.innodb_trx
             WHERE trx_mysql_thread_id = CONNECTION_ID()'
        );

        return (string) ($row->trx_id ?? '');
    }

    /**
     * The InnoDB transaction ids that exist right now.
     *
     * @return array<int, string>
     */
    private function knownTransactionIds(): array
    {
        $rows = DB::select('SELECT trx_id FROM information_schema.innodb_trx');

        return array_map(fn ($row) => (string) $row->trx_id, $rows);
    }

    /**
     * The deadlock InnoDB recorded last, as the server itself reports it.
     */
    private function latestDeadlockReport(): string
    {
        $status = '';

        foreach (DB::select('SHOW ENGINE INNODB STATUS') as $row) {
            $status = (string) ($row->Status ?? '');
        }

        $position = strpos($status, 'LATEST DETECTED DEADLOCK');

        return $position === false ? '' : substr($status, $position);
    }

    /**
     * The result a worker wrote.
     *
     * @return array<string, mixed>
     */
    private function collectWorkerResult(string $barrier, int $worker): array
    {
        $this->waitFor($barrier, 'result.', $worker);

        $files = glob($barrier.'/result.*') ?: [];

        foreach ($files as $file) {
            $result = json_decode((string) file_get_contents($file), true);

            if (is_array($result)) {
                return $result;
            }
        }

        $this->fail('The worker did not write a result.');
    }
}
