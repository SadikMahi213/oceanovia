<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * F-06.5 / P1 - one cart per user.
 *
 * A unique index on carts.user_id is the invariant every cart path relies on.
 * Locking a cart row that does not exist yet does not create one: under
 * REPEATABLE READ the gap locks two transactions take on the same range are
 * compatible with each other, so both of them could insert. The index is what
 * makes a concurrent creation safe, and the conflict it raises is the signal
 * that another request won the race.
 *
 * SCOPE OF THESE TESTS
 * --------------------
 * They run on SQLite in-memory, where a unique index is enforced but row
 * locking is not (the SQLite grammar compiles lockForUpdate() to an empty
 * string). They prove the index, the guard, the conflict recovery and the
 * unchanged contracts. They do NOT prove simultaneous-request safety on
 * MySQL/InnoDB; that needs a real engine with two independent connections.
 */
class CartUniquenessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The migration under test. It is loaded once, because the migration file
     * declares an anonymous class that cannot be declared twice.
     */
    private static mixed $migration = null;


    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // The path is resolved from the test file, because the application is
        // not booted yet at this point.
        self::$migration = require dirname(__DIR__, 2)
            .'/database/migrations/2026_10_21_000002_add_unique_user_id_to_carts_table.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The migrator already applied this migration, so the index is part of
        // the test schema. The tests below exercise the migration from its
        // pre-migration state, so the migration's own rollback is used to
        // restore that state: on MySQL the unique index also supports the
        // carts.user_id foreign key, so the index cannot be dropped on its
        // own.
        //
        // SQLite treats DDL as transactional, so the test transaction's
        // rollback restores the migrated schema for every other test.
        $this->removeDuplicateCarts();
        $this->restorePreMigrationSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * The index is asserted by its effect, not by reading the schema, so the
     * test works on every engine the project supports.
     */
    private function assertDuplicateCartIsRejected(User $user): void
    {
        if (! Cart::where('user_id', $user->id)->exists()) {
            Cart::create(['user_id' => $user->id]);
        }

        $thrown = null;

        try {
            Cart::create(['user_id' => $user->id]);
        } catch (UniqueConstraintViolationException $e) {
            $thrown = $e;
        }

        $this->assertNotNull(
            $thrown,
            'A second cart for the same user must be rejected by the unique index.'
        );
    }

    private function assertDuplicateCartIsAllowed(User $user): void
    {
        if (! Cart::where('user_id', $user->id)->exists()) {
            Cart::create(['user_id' => $user->id]);
        }

        $second = Cart::create(['user_id' => $user->id]);

        $this->assertNotNull($second->id, 'A second cart for the same user was still allowed.');
    }

    private function createUserIdUniqueIndex(): void
    {
        self::$migration->up();
    }

    private function dropUserIdUniqueIndex(): void
    {
        try {
            Schema::table('carts', fn (Blueprint $table) => $table->dropUnique(CartService::USER_ID_UNIQUE_INDEX));
        } catch (\Throwable) {
            // The index was not there.
        }
    }

    /**
     * Put the carts table back into its pre-migration state.
     *
     * The migration's own rollback is used, because on MySQL the unique index
     * also supports the carts.user_id foreign key and cannot be dropped while
     * that constraint exists.
     */
    private function restorePreMigrationSchema(): void
    {
        try {
            self::$migration->down();
        } catch (\Throwable) {
            // The table was already in its pre-migration state.
        }
    }

    /**
     * Remove the duplicate carts a test may have created, so the unique
     * index can be restored afterwards.
     */
    private function removeDuplicateCarts(): void
    {
        $duplicatedUsers = DB::table('carts')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        if ($duplicatedUsers->isNotEmpty()) {
            Cart::whereIn('user_id', $duplicatedUsers)->delete();
        }
    }

    private function customer(): User
    {
        return User::factory()->create(['role_type' => 'customer']);
    }

    private function product(): Product
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => 19.99,
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);

        return $product;
    }

    /*
    |--------------------------------------------------------------------------
    | The migration
    |--------------------------------------------------------------------------
    */

    public function test_the_migration_creates_the_unique_index(): void
    {
        $user = $this->customer();

        $this->createUserIdUniqueIndex();

        $this->assertDuplicateCartIsRejected($user);
    }

    public function test_the_migration_refuses_to_proceed_when_duplicate_user_carts_exist(): void
    {
        $user = $this->customer();

        $first = Cart::create(['user_id' => $user->id]);
        $second = Cart::create(['user_id' => $user->id]);

        $thrown = null;

        try {
            self::$migration->up();
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The migration was expected to abort on duplicate carts.');
        $this->assertStringContainsString('more than one cart', $thrown->getMessage());
        $this->assertStringContainsString((string) $user->id, $thrown->getMessage());

        // Nothing was merged, deleted or changed, and no index was added.
        $this->assertSame(2, Cart::where('user_id', $user->id)->count());
        $this->assertNotNull(Cart::find($first->id));
        $this->assertNotNull(Cart::find($second->id));

        $this->assertDuplicateCartIsAllowed($user);
    }

    public function test_the_migration_can_be_rolled_back(): void
    {
        $user = $this->customer();

        $this->createUserIdUniqueIndex();

        $this->assertDuplicateCartIsRejected($user);

        self::$migration->down();

        $this->assertDuplicateCartIsAllowed($user);
    }

    /*
    |--------------------------------------------------------------------------
    | One cart per user
    |--------------------------------------------------------------------------
    */

    public function test_repeated_cart_creation_for_one_user_returns_the_same_cart(): void
    {
        $user = $this->customer();
        $service = app(CartService::class);

        $first = $service->lockForUser($user->id);
        $second = $service->lockForUser($user->id);
        $read = $service->findOrCreateForUser($user->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->id, $read->id);
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }

    public function test_a_uniqueness_conflict_is_recovered_by_fetching_the_existing_cart(): void
    {
        $user = $this->customer();

        $this->createUserIdUniqueIndex();

        // A competing request creates the cart first, right before this
        // request's own insert reaches the database.
        Event::listen('eloquent.creating: '.Cart::class, function () use ($user): void {
            DB::table('carts')->insert([
                'user_id'    => $user->id,
                'session_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $cart = app(CartService::class)->lockForUser($user->id);

        $this->assertSame($user->id, $cart->user_id);
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }

    public function test_an_unrelated_database_exception_is_not_swallowed(): void
    {
        $user = $this->customer();

        $this->createUserIdUniqueIndex();

        Event::listen('eloquent.creating: '.Cart::class, function (): void {
            throw new RuntimeException('cart write failed');
        });

        $thrown = null;

        try {
            app(CartService::class)->lockForUser($user->id);
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'The unrelated failure must not be swallowed.');
        $this->assertSame('cart write failed', $thrown->getMessage());
        $this->assertSame(0, Cart::where('user_id', $user->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Existing behaviour is unchanged
    |--------------------------------------------------------------------------
    */

    public function test_existing_cart_retrieval_and_guest_behaviour_are_unchanged(): void
    {
        $user = $this->customer();
        $product = $this->product();

        // A guest add answers with the guest summary and stores no cart.
        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 2,
        ])->assertStatus(200)->assertJson(['cart_count' => 2]);

        $this->assertSame(0, Cart::count());

        // The signed in customer gets their own cart.
        $this->actingAs($user)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'    => 1,
        ])->assertStatus(200);

        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }

    public function test_the_api_cart_endpoint_response_shape_and_creation_behaviour_are_unchanged(): void
    {
        $user = $this->customer();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/cart')->assertStatus(200);

        $response->assertJsonStructure([
            'data' => ['cart_id', 'items', 'total', 'items_count'],
        ]);
        $response->assertJsonPath('data.items', []);
        $response->assertJsonPath('data.items_count', 0);
        $response->assertJsonPath('data.total', 0);

        // The endpoint still creates the cart when the user has none.
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());

        // A second call returns the same cart instead of creating another.
        $second = $this->getJson('/api/v1/cart')->assertStatus(200);

        $second->assertJsonPath('data.cart_id', $response->json('data.cart_id'));
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }
}
