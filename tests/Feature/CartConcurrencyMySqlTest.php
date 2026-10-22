<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;

/**
 * F-06.9 - real-engine verification of the cart concurrency protections.
 *
 * These tests need a real MySQL/InnoDB server, because SQLite compiles
 * lockForUpdate() to an empty string and has no row-level locking. They are
 * therefore gated on an explicitly configured disposable test database:
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
 * Each race uses two independent worker processes, so the two requests hold
 * separate database connections and genuinely overlap.
 */
class CartConcurrencyMySqlTest extends TestCase
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
                .'and MYSQL_TEST_DB_PASSWORD to run the real-engine cart concurrency tests. They need a '
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
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);
    }

    /**
     * Place a cash on delivery order for the given products.
     *
     * @param  array<int, Product>  $products
     */
    private function checkoutCod(User $customer, array $products, Address $address): Order
    {
        $cart = Cart::create(['user_id' => $customer->id]);

        foreach ($products as $product) {
            CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'quantity'   => 1,
                'unit_price' => $product->price,
            ]);
        }

        $this->actingAs($customer)->post('/checkout', [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        return Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
    }

    private function product(User $seller, float $price = 25.00): Product
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $price,
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);

        return $product;
    }

    /**
     * Run the given workers at the same time and collect their results.
     *
     * @param  array<int, array<string, mixed>>  $workers
     * @return array<int, array<string, mixed>>
     */
    private function runConcurrently(array $workers): array
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cart_concurrency_'.bin2hex(random_bytes(6));

        mkdir($barrier, 0777, true);

        foreach ($workers as $worker) {
            $payloadFile = $barrier.'/payload.'.$worker['worker'].'.json';
            file_put_contents($payloadFile, (string) json_encode($worker));

            $command = escapeshellarg(PHP_BINARY)
                .' '.escapeshellarg(base_path('tests/Support/CartConcurrencyWorker.php'))
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
    | Concurrent first-cart creation
    |--------------------------------------------------------------------------
    */

    public function test_two_concurrent_web_cart_additions_for_a_new_user_end_with_one_cart(): void
    {
        $seller = $this->seller();
        $product = $this->product($seller);
        $customer = $this->customer();

        $results = $this->runConcurrently([
            [
                'worker'   => 1,
                'channel'  => 'web',
                'user_id'  => $customer->id,
                'uri'      => '/cart/add',
                'input'    => ['product_id' => $product->id, 'quantity' => 2],
            ],
            [
                'worker'   => 2,
                'channel'  => 'web',
                'user_id'  => $customer->id,
                'uri'      => '/cart/add',
                'input'    => ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
            $this->assertSame(200, $result['status'], 'A concurrent cart addition did not succeed.');
        }

        $carts = Cart::where('user_id', $customer->id)->get();

        $this->assertCount(1, $carts, 'The user must end up with exactly one cart.');
        $this->assertSame(1, (int) CartItem::where('cart_id', $carts->first()->id)->count());
        $this->assertSame(4, (int) CartItem::where('cart_id', $carts->first()->id)->sum('quantity'));
    }

    public function test_two_concurrent_api_cart_additions_for_a_new_user_end_with_one_cart(): void
    {
        $seller = $this->seller();
        $product = $this->product($seller);
        $customer = $this->customer();

        $results = $this->runConcurrently([
            [
                'worker'   => 1,
                'channel'  => 'api',
                'user_id'  => $customer->id,
                'uri'      => '/api/v1/cart/add',
                'input'    => ['product_id' => $product->id, 'quantity' => 2],
            ],
            [
                'worker'   => 2,
                'channel'  => 'api',
                'user_id'  => $customer->id,
                'uri'      => '/api/v1/cart/add',
                'input'    => ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
            $this->assertSame(200, $result['status'], 'A concurrent cart addition did not succeed.');
        }

        $carts = Cart::where('user_id', $customer->id)->get();

        $this->assertCount(1, $carts, 'The user must end up with exactly one cart.');
        $this->assertSame(4, (int) CartItem::where('cart_id', $carts->first()->id)->sum('quantity'));
    }

    public function test_two_concurrent_additions_to_an_existing_cart_do_not_lose_an_update(): void
    {
        $seller = $this->seller();
        $product = $this->product($seller);
        $customer = $this->customer();

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        $results = $this->runConcurrently([
            [
                'worker'   => 1,
                'channel'  => 'web',
                'user_id'  => $customer->id,
                'uri'      => '/cart/add',
                'input'    => ['product_id' => $product->id, 'quantity' => 3],
            ],
            [
                'worker'   => 2,
                'channel'  => 'web',
                'user_id'  => $customer->id,
                'uri'      => '/cart/add',
                'input'    => ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
            $this->assertSame(200, $result['status']);
        }

        $this->assertSame(1, Cart::where('user_id', $customer->id)->count());
        $this->assertSame(7, (int) CartItem::where('cart_id', $cart->id)->sum('quantity'));
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout racing a cancellation
    |--------------------------------------------------------------------------
    */

    public function test_a_checkout_racing_a_cancellation_of_the_same_products_completes_without_a_deadlock(): void
    {
        $seller = $this->seller();
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        $category = Category::factory()->create(['status' => true]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id'     => $supplier->id,
            'category_id'     => $category->id,
            'wholesale_price' => 10.00,
            'status'          => 'published',
        ]);

        SupplierProductStock::factory()->create([
            'supplier_product_id' => $supplierProduct->id,
            'stock_quantity'      => 10,
            'reserved_quantity'   => 0,
        ]);


        $customer = $this->customer();
        $address = $this->addressFor($customer);

        // Two products of each kind, so the two stock locks are held over a
        // wider window and the race has a realistic chance to interleave.
        $sourcedProducts = [];
        $localProducts = [];

        for ($i = 0; $i < 2; $i++) {
            $sourcedProducts[] = Product::factory()->create([
                'seller_id'           => $seller->id,
                'category_id'         => $category->id,
                'status'              => 'published',
                'price'               => 25.00,
                'supplier_product_id' => $supplierProduct->id,
                'sourcing_price'      => 10.00,
            ]);

            $localProducts[] = Product::factory()->create([
                'seller_id'   => $seller->id,
                'category_id' => $category->id,
                'status'      => 'published',
                'price'       => 25.00,
            ]);

            Inventory::create([
                'product_id'     => $localProducts[$i]->id,
                'stock_quantity' => 10,
            ]);
        }

        // The order that is cancelled while the new one is placed. It holds
        // both kinds of product, so its cancellation restores the local
        // inventory and releases the supplier source stock, which are exactly
        // the two rows the checkout takes.
        $this->checkoutCod($customer, array_merge($sourcedProducts, $localProducts), $address);

        $oldOrder = Order::where('user_id', $customer->id)->firstOrFail();

        $this->assertSame(9, (int) Inventory::where('product_id', $localProducts[0]->id)->firstOrFail()->stock_quantity);
        $this->assertSame(2, (int) SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail()->reserved_quantity);

        // The cart for the racing checkout. The customer already has the one
        // cart row, so the products are put back into it.
        $cart = Cart::where('user_id', $customer->id)->firstOrFail();

        foreach (array_merge($sourcedProducts, $localProducts) as $product) {
            CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->price]);
        }

        $results = $this->runConcurrently([
            [
                'worker'  => 1,
                'channel' => 'web',
                'user_id' => $customer->id,
                'uri'     => '/checkout',
                'input'   => ['shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1'],
            ],
            [
                'worker'  => 2,
                'channel' => 'web',
                'user_id' => $customer->id,
                'uri'     => '/account/orders/'.$oldOrder->id.'/cancel',
                'input'   => ['reason' => 'Racing a checkout'],
            ],
        ]);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], 'The worker failed: '.($result['error'] ?? 'unknown'));
            $this->assertNotSame(500, $result['status'], 'The operation failed: '.$result['status'].' '.$result['body']);
            $this->assertStringNotContainsStringIgnoringCase('deadlock', (string) $result['body']);
        }

        // Both operations completed.
        $this->assertSame('cancelled', $oldOrder->fresh()->status);

        $newOrder = Order::where('user_id', $customer->id)
            ->where('id', '!=', $oldOrder->id)
            ->firstOrFail();

        $this->assertSame(4, $newOrder->items()->count());

        // Stock conservation: the cancelled order gave its unit back and the
        // new order took one, so the local inventory is back to where the
        // single live order left it, and the supplier source stock is
        // reserved exactly once.
        foreach ($localProducts as $localProduct) {
            $this->assertSame(
                9,
                (int) Inventory::where('product_id', $localProduct->id)->firstOrFail()->stock_quantity,
                'The local inventory was not conserved.'
            );
        }

        $sourceStock = SupplierProductStock::where('supplier_product_id', $supplierProduct->id)->firstOrFail();

        $this->assertSame(10, (int) $sourceStock->stock_quantity);
        $this->assertSame(
            2,
            (int) $sourceStock->reserved_quantity,
            'The supplier source stock was not reserved exactly once.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The unique index migration on a real engine
    |--------------------------------------------------------------------------
    */

    public function test_the_cart_uniqueness_migration_can_be_rolled_back_and_reapplied_on_mysql(): void
    {
        $migration = require base_path('database/migrations/2026_10_21_000002_add_unique_user_id_to_carts_table.php');

        $this->assertTrue($this->hasUniqueIndex('carts', 'carts_user_id_unique'), 'The unique index is missing before the rollback.');

        // Roll back: the foreign key has to be restored with its own index.
        $migration->down();

        $this->assertFalse(
            $this->hasUniqueIndex('carts', 'carts_user_id_unique'),
            'The unique index was not removed by the rollback.'
        );

        $foreignKeys = $this->foreignKeys('carts');

        $this->assertArrayHasKey('carts_user_id_foreign', $foreignKeys, 'The carts.user_id foreign key was not restored.');
        $this->assertSame(
            ['column' => 'user_id', 'referenced_table' => 'users', 'on_delete' => 'CASCADE'],
            $foreignKeys['carts_user_id_foreign'],
            'The restored foreign key does not match the original definition.'
        );

        // The supporting index of the foreign key must exist again, and it must
        // not be a second copy of the unique index.
        $this->assertTrue(
            $this->hasIndexOnColumn('carts', 'user_id'),
            'The foreign key has no supporting index on carts.user_id.'
        );

        // Re-apply.
        $migration->up();

        $this->assertTrue($this->hasUniqueIndex('carts', 'carts_user_id_unique'), 'The unique index was not restored.');
        $this->assertArrayHasKey('carts_user_id_foreign', $this->foreignKeys('carts'));
    }

    private function hasUniqueIndex(string $table, string $index): bool
    {
        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            if ($row->Key_name === $index) {
                return (int) $row->Non_unique === 0;
            }
        }

        return false;
    }

    private function hasIndexOnColumn(string $table, string $column): bool
    {
        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            if ($row->Column_name === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function foreignKeys(string $table): array
    {
        $keys = [];

        foreach (DB::select(
            'SELECT k.CONSTRAINT_NAME AS constraint_name, k.COLUMN_NAME AS column_name, '
            .'k.REFERENCED_TABLE_NAME AS referenced_table, r.DELETE_RULE AS delete_rule '
            .'FROM information_schema.KEY_COLUMN_USAGE k '
            .'JOIN information_schema.REFERENTIAL_CONSTRAINTS r '
            .'ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.TABLE_NAME = k.TABLE_NAME '
            .'AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
            .'WHERE k.TABLE_SCHEMA = DATABASE() '
            ."AND k.TABLE_NAME = '{$table}' AND k.REFERENCED_TABLE_NAME IS NOT NULL"
        ) as $row) {
            $keys[$row->constraint_name] = [
                'column'          => $row->column_name,
                'referenced_table' => $row->referenced_table,
                'on_delete'       => $row->delete_rule,
            ];
        }

        return $keys;
    }
}
