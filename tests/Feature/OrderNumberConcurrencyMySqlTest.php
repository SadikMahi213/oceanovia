<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * R3.12-F4 - the order number of concurrently created orders on a real server.
 *
 * The order number is unique in the database, and two orders that are created
 * at the same moment must never carry the same number. uniqid() could not
 * guarantee that: it is derived from the clock alone, so two processes that
 * created an order in the same microsecond produced the same number, and the
 * second insert failed with MySQL 1062 instead of an order.
 *
 * The test is gated on an explicitly configured disposable test database,
 * exactly like the other concurrency harnesses:
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
 * Every worker is a separate process with its own database connection, and
 * all of them are released at the same moment by a barrier, so the orders are
 * really created at the same time.
 */
class OrderNumberConcurrencyMySqlTest extends TestCase
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
                .'and MYSQL_TEST_DB_PASSWORD to run the real-engine order number tests. They need a '
                .'disposable MySQL/InnoDB database and must never be pointed at production.'
            );
        }

        if (! str_contains(strtolower((string) $database), 'test')) {
            $this->markTestSkipped(
                'The configured MYSQL_TEST_DB_DATABASE name must contain "test": this class rebuilds the schema.'
            );
        }

        $settings = [
            'DB_CONNECTION'  => 'mysql',
            'DB_HOST'        => $host,
            'DB_PORT'        => (string) env('MYSQL_TEST_DB_PORT', '3306'),
            'DB_DATABASE'    => $database,
            'DB_USERNAME'    => (string) env('MYSQL_TEST_DB_USERNAME', 'root'),
            'DB_PASSWORD'    => (string) env('MYSQL_TEST_DB_PASSWORD', ''),
            'SESSION_DRIVER' => 'array',
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
    | Concurrent order numbers
    |--------------------------------------------------------------------------
    */

    public function test_concurrent_orders_receive_distinct_order_numbers(): void
    {
        $users = User::factory()->count(4)->create();

        $results = $this->runConcurrently(
            $users->map(fn (User $user, int $index) => [
                'worker'    => $index + 1,
                'operation' => 'create_order',
                'user_id'   => $user->id,
            ])->all()
        );

        $this->assertCount(4, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
            $this->assertSame(
                [],
                $result['db_failures'] ?? [],
                'No worker may run into a database error while creating an order.'
            );
        }

        $numbers = array_map(fn (array $result) => (string) ($result['order_number'] ?? ''), $results);

        $this->assertCount(
            4,
            array_unique($numbers),
            'Every concurrently created order must carry its own order number, got: '.json_encode($numbers)
        );

        $this->assertSame(4, Order::query()->count(), 'Every concurrently created order must be stored.');

        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression(
                '/^ORD-[0-9A-HJKMNP-TV-Z]{26}$/',
                $number,
                'The order number must keep the canonical shape: '.$number
            );
        }
    }

    public function test_processes_create_different_numbers_for_the_same_instant(): void
    {
        $users = User::factory()->count(4)->create();

        // The same instant for every worker. The number of an instant is only
        // unique because the generator mixes in randomness, so two processes
        // that ask for the number of the same moment must get different
        // numbers - that is what keeps concurrent orders apart.
        $instant = '2026-01-01 12:00:00.000000';

        $results = $this->runConcurrently(
            $users->map(fn (User $user, int $index) => [
                'worker'    => $index + 1,
                'operation' => 'create_order',
                'user_id'   => $user->id,
                'instant'   => $instant,
            ])->all()
        );

        $this->assertCount(4, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
            $this->assertNotEmpty(
                $result['instant_number'] ?? null,
                'The worker must report the number of the requested instant.'
            );
        }

        $numbers = array_map(fn (array $result) => (string) ($result['instant_number'] ?? ''), $results);

        $this->assertCount(
            4,
            array_unique($numbers),
            'Two processes must never produce the same order number for the same instant, got: '.json_encode($numbers)
        );
    }

    public function test_mysql_still_rejects_a_duplicate_order_number(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id'        => $user->id,
            'subtotal'       => 25.00,
            'shipping_cost'  => 0,
            'tax'            => 0,
            'discount'       => 0,
            'total'          => 25.00,
            'status'         => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $failure = null;

        try {
            Order::query()->create([
                'user_id'        => $user->id,
                'order_number'   => $order->order_number,
                'subtotal'       => 25.00,
                'shipping_cost'  => 0,
                'tax'            => 0,
                'discount'       => 0,
                'total'          => 25.00,
                'status'         => 'pending',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
            ]);
        } catch (QueryException $e) {
            $failure = $e;
        }

        $this->assertNotNull(
            $failure,
            'The unique index on the order number must reject a duplicate: the number is the last line of defence.'
        );

        $this->assertSame(
            1062,
            (int) ($failure->errorInfo[1] ?? 0),
            'MySQL must report a duplicate entry (1062) for a repeated order number.'
        );

        $this->assertSame(
            '23000',
            (string) ($failure->errorInfo[0] ?? ''),
            'A duplicate entry reports SQLSTATE 23000, not the 40001 of a deadlock.'
        );

        $this->assertSame(1, Order::query()->count(), 'The rejected order must not be stored.');
    }

    public function test_a_worker_reports_the_duplicate_key_error_the_server_returned(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id'        => $user->id,
            'subtotal'       => 25.00,
            'shipping_cost'  => 0,
            'tax'            => 0,
            'discount'       => 0,
            'total'          => 25.00,
            'status'         => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        $results = $this->runConcurrently([[
            'worker'       => 1,
            'operation'    => 'create_duplicate_order',
            'user_id'      => $user->id,
            'order_number' => $order->order_number,
        ]]);

        $result = $results[0];

        $this->assertFalse($result['ok'] ?? true, 'The worker must report the failure it ran into.');

        $this->assertSame(
            1062,
            (int) ($result['driver_code'] ?? 0),
            'The worker must report the duplicate key error the server returned, got: '.json_encode($result)
        );

        $this->assertSame(
            '23000',
            (string) ($result['sqlstate'] ?? ''),
            'A duplicate key reports SQLSTATE 23000.'
        );

        $this->assertNotSame(
            1213,
            (int) ($result['driver_code'] ?? 0),
            'A duplicate key must never be reported as a deadlock.'
        );

        $this->assertSame(1, Order::query()->count(), 'The rejected order must not be stored.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Run the given workers at the same time and collect their results.
     *
     * @param  array<int, array<string, mixed>>  $workers
     * @return array<int, array<string, mixed>>
     */
    private function runConcurrently(array $workers): array
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'order_number_concurrency_'.bin2hex(random_bytes(6));

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

        // Release every worker at the same moment.
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
}
