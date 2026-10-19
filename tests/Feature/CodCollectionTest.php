<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CodCollection;
use App\Models\Commission;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerBalance;
use App\Models\SellerProfile;
use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use App\Models\User;
use App\Services\CodCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);
    }

    private function seller(float $rate = 10.0): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => $rate,
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

    private function retailProduct(User $seller, float $price = 100.00): Product
    {
        return Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => $price,
            'status'    => 'published',
        ]);
    }

    private function checkoutCod(User $customer, Product $product, int $quantity, Address $address)
    {
        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
        ]);
    }

    public function test_cod_checkout_creates_one_pending_collection_per_item(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 2, $address)->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, CodCollection::count());

        $collection = CodCollection::firstOrFail();
        $this->assertSame('pending', $collection->status);
        $this->assertSame('seller', $collection->collector_role);
        $this->assertSame($seller->id, $collection->collector_id);
        $this->assertEquals(round((float) $order->total, 2), (float) $collection->expected_amount);
        $this->assertEquals(0.00, (float) $collection->collected_amount);
    }

    public function test_sourced_item_collector_is_supplier_from_snapshot(): void
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
        SupplierProductStock::factory()->create(['supplier_product_id' => $sp->id, 'stock_quantity' => 10]);

        $product = Product::factory()->create([
            'seller_id'           => $seller->id,
            'category_id'         => $category->id,
            'supplier_product_id' => $sp->id,
            'sourcing_price'      => 40.00,
            'price'               => 79.99,
            'status'              => 'published',
        ]);

        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $this->assertSame('supplier', $collection->collector_role);
        $this->assertSame($supplier->id, $collection->collector_id);
    }

    public function test_expected_amount_includes_proportional_shipping_and_tax(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $order = Order::firstOrFail();
        $item = $order->items()->firstOrFail();
        $expected = (new CodCollectionService())->expectedAmountForItem($order, $item);

        $this->assertEquals(round((float) $order->total, 2), $expected);
    }

    public function test_admin_recording_full_collection_credits_seller_net(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => $collection->expected_amount,
        ])->assertRedirect(route('admin.cod-collections.show', $collection));

        $collection = $collection->fresh();
        $this->assertSame('collected', $collection->status);
        $this->assertFalse($collection->requires_review);
        $this->assertSame($admin->id, $collection->collected_by);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(90.00, (float) $balance->balance);
    }

    public function test_shortfall_credits_actual_and_flags_review(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $expected = (float) $collection->expected_amount;

        $this->actingAs($this->admin())->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => 50.00,
        ])->assertRedirect();

        $collection = $collection->fresh();
        $this->assertTrue($collection->requires_review);
        $this->assertEquals(50.00, (float) $collection->collected_amount);
        $this->assertEquals(round($expected - 50.00, 2), (float) $collection->shortfall_amount);

        $expectedCredit = round(90.00 * (50.00 / $expected), 2);
        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals($expectedCredit, (float) $balance->balance);
    }

    public function test_over_collection_never_over_credits(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $expected = (float) $collection->expected_amount;

        $this->actingAs($this->admin())->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => $expected + 50.00,
        ])->assertRedirect();

        $collection = $collection->fresh();
        $this->assertEquals(50.00, (float) $collection->overpaid_amount);
        $this->assertFalse($collection->requires_review);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(90.00, (float) $balance->balance);
    }

    public function test_recording_twice_is_idempotent(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $admin = $this->admin();
        $expected = (float) $collection->expected_amount;

        $this->actingAs($admin)->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => $expected,
        ]);

        $this->actingAs($admin)->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => $expected,
        ]);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(90.00, (float) $balance->balance);
    }

    public function test_non_admin_cannot_record_collection(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();

        $this->actingAs($seller)->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => 100.00,
        ])->assertForbidden();

        $this->assertSame('pending', $collection->fresh()->status);
    }

    public function test_non_admin_cannot_view_collection_queue(): void
    {
        $this->actingAs($this->seller())->get(route('admin.cod-collections.index'))->assertForbidden();
    }

    public function test_admin_can_view_collection_queue_and_detail(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.cod-collections.index'))
            ->assertOk()
            ->assertSee($collection->order->order_number);

        $this->actingAs($admin)->get(route('admin.cod-collections.show', $collection))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_cancelled_order_collection_cannot_be_recorded(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $order = Order::firstOrFail();
        $order->update(['status' => 'cancelled']);

        $collection = CodCollection::firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => 100.00,
        ])->assertSessionHasErrors('collected_amount');

        $this->assertSame('pending', $collection->fresh()->status);
        $this->assertSame(0, SellerBalance::count());
    }

    public function test_admin_order_page_shows_cod_collection_status(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $order = Order::firstOrFail();

        $this->actingAs($this->admin())->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('COD')
            ->assertSee('Pending');
    }

    public function test_commission_exists_for_cod_order(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $this->assertSame(1, Commission::count());
        $this->assertEquals(10.00, (float) Commission::firstOrFail()->amount);
    }

    public function test_admin_collection_summary_reflects_filters(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();
        $admin = $this->admin();

        $collection = CodCollection::firstOrFail();
        $expected = (float) $collection->expected_amount;
        $collected = round($expected - 20.00, 2);

        $this->actingAs($admin)->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => $collected,
        ])->assertRedirect();

        $summary = $this->actingAs($admin)->get(route('admin.cod-collections.index'))
            ->assertOk()
            ->viewData('summary');

        $this->assertSame(1, (int) $summary->total);
        $this->assertEquals($expected, round((float) $summary->expected, 2));
        $this->assertEquals($collected, round((float) $summary->collected, 2));
        $this->assertEquals(20.00, round((float) $summary->shortfall, 2));
        $this->assertEquals(0.00, round((float) $summary->overpaid, 2));

        $filtered = $this->actingAs($admin)->get(route('admin.cod-collections.index', ['review' => 1]))
            ->assertOk()
            ->viewData('summary');

        $this->assertSame(1, (int) $filtered->total);

        $pendingOnly = $this->actingAs($admin)->get(route('admin.cod-collections.index', ['status' => 'pending']))
            ->assertOk()
            ->viewData('summary');

        $this->assertSame(0, (int) $pendingOnly->total);
    }

    public function test_admin_can_export_cod_collections_csv(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $order = Order::firstOrFail();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.cod-collections.export'))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Order', $content);
        $this->assertStringContainsString('Collector Role', $content);
        $this->assertStringContainsString($order->order_number, $content);
        $this->assertStringContainsString($product->name, $content);
    }

    public function test_non_admin_cannot_export_collections(): void
    {
        $this->actingAs($this->seller())->get(route('admin.cod-collections.export'))->assertForbidden();
    }

    public function test_shortfall_queues_admin_review_email(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => 50.00,
        ])->assertRedirect();

        $this->assertDatabaseHas('email_logs', [
            'user_id'         => $admin->id,
            'recipient_email' => $admin->email,
            'status'          => 'sent',
        ]);

        $log = EmailLog::where('user_id', $admin->id)->firstOrFail();
        $this->assertStringContainsString('needs review', $log->subject);
    }

    public function test_full_collection_does_not_queue_admin_review_email(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->retailProduct($seller, 100.00);

        $this->checkoutCod($customer, $product, 1, $address)->assertRedirect();

        $collection = CodCollection::firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.cod-collections.record', $collection), [
            'collected_amount' => $collection->expected_amount,
        ])->assertRedirect();

        $this->assertSame(0, EmailLog::where('subject', 'like', '%needs review%')->count());

        $sellerLog = EmailLog::where('user_id', $seller->id)->firstOrFail();
        $this->assertStringContainsString('credited to your balance', $sellerLog->subject);
    }
}
