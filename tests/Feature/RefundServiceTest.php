<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\SellerBalance;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PayoutService;
use App\Services\RefundService;
use App\Services\StripeRefundGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakeStripeRefundGateway;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private function orderForSeller(User $seller, float $subtotal, string $paymentMethod = 'cod'): Order
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $order = Order::create([
            'user_id'        => $buyer->id,
            'subtotal'       => $subtotal,
            'total'          => $subtotal,
            'status'         => 'delivered',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => 'Test Product',
            'quantity'     => 1,
            'unit_price'   => $subtotal,
            'subtotal'     => $subtotal,
        ]);

        return $order->fresh('items');
    }

    private function refundFor(Order $order, User $buyer): Refund
    {
        return Refund::create([
            'order_id' => $order->id,
            'user_id'  => $buyer->id,
            'amount'   => $order->total,
            'reason'   => 'Test refund',
            'status'   => 'pending',
        ]);
    }

    public function test_refund_of_uncredited_cod_order_never_creates_negative_balance(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'cod');

        (new CommissionService())->createCommissions($order);

        $balance = (new PayoutService())->getBalance($seller->id);
        $this->assertEquals(0.00, (float) $balance->balance);

        $refund = $this->refundFor($order, $order->user);
        $service = new RefundService();
        $service->approve($refund, $order->user->id, 'approved', 'cash');
        $service->complete($refund->fresh(), $order->user->id);

        $balance = $balance->fresh();
        $this->assertEquals(0.00, (float) $balance->balance);
        $this->assertEquals(0.00, (float) $balance->total_earned);
        $this->assertGreaterThanOrEqual(0, (float) $balance->balance);

        $this->assertSame('reversed', Commission::firstOrFail()->status);
        $this->assertSame('refunded', $order->fresh()->status);
    }

    public function test_refund_reverses_credited_net_exactly(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'cod');

        $commissionService = new CommissionService();
        $commissionService->createCommissions($order);

        $net = $commissionService->netForItem($order->items->first());
        $this->assertEquals(90.00, $net);

        $payoutService = new PayoutService();
        $payoutService->credit($seller->id, $net);

        $refund = $this->refundFor($order, $order->user);
        $service = new RefundService();
        $service->approve($refund, $order->user->id, 'approved', 'cash');
        $service->complete($refund->fresh(), $order->user->id);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $balance->balance);
        $this->assertEquals(0.00, (float) $balance->total_earned);
    }

    public function test_refund_cannot_reverse_more_than_available_balance(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'cod');

        (new CommissionService())->createCommissions($order);

        (new PayoutService())->credit($seller->id, 30.00);

        $refund = $this->refundFor($order, $order->user);
        $service = new RefundService();
        $service->approve($refund, $order->user->id, 'approved', 'cash');
        $service->complete($refund->fresh(), $order->user->id);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $balance->balance);
        $this->assertGreaterThanOrEqual(0, (float) $balance->balance);
    }

    public function test_cod_refund_requires_method_and_completes_separately(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'cod');
        (new CommissionService())->createCommissions($order);

        $refund = $this->refundFor($order, $order->user);
        $admin = User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);

        $this->actingAs($admin)->post(route('admin.refunds.approve', $refund), [
            'notes' => 'Approved',
        ])->assertSessionHasErrors('refund_method');

        $this->actingAs($admin)->post(route('admin.refunds.approve', $refund), [
            'refund_method' => 'cash',
            'notes'         => 'Customer refunded in cash',
        ])->assertRedirect(route('admin.refunds.show', $refund));

        $refund = $refund->fresh();
        $this->assertSame('approved', $refund->status);
        $this->assertSame('cash', $refund->refund_method);
        $this->assertSame('delivered', $order->fresh()->status);

        $this->actingAs($admin)->get(route('admin.refunds.show', $refund))
            ->assertOk()
            ->assertSee('Complete Refund');
        $this->actingAs($admin)->get(route('admin.refunds.index'))->assertOk();

        $this->actingAs($admin)->post(route('admin.refunds.complete', $refund), [])
            ->assertRedirect(route('admin.refunds.show', $refund));

        $this->assertSame('completed', $refund->fresh()->status);
        $this->assertNotNull($refund->fresh()->completed_at);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('reversed', Commission::firstOrFail()->status);
    }

    public function test_stripe_refund_without_payment_intent_stays_approved(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'stripe');
        (new CommissionService())->createCommissions($order);

        $refund = $this->refundFor($order, $order->user);

        (new RefundService())->approve($refund, $order->user->id, 'approved');

        $refund = $refund->fresh();
        $this->assertSame('approved', $refund->status);
        $this->assertSame('stripe', $refund->refund_method);
        $this->assertSame('delivered', $order->fresh()->status);
    }

    /**
     * The refund identity of the external operation.
     *
     * The columns exist, they are empty for the refunds that were made
     * before them, and the database refuses a second refund that claims the
     * same key or the same provider refund.
     */
    public function test_the_refund_carries_the_identity_of_its_external_operation(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'cod');

        $refund = $this->refundFor($order, $order->user);

        $this->assertTrue(
            Schema::hasColumn('refunds', 'stripe_idempotency_key'),
            'The refund must carry the idempotency key of its provider call.'
        );
        $this->assertTrue(Schema::hasColumn('refunds', 'stripe_refund_id'));
        $this->assertTrue(Schema::hasColumn('refunds', 'stripe_refunded_at'));

        $this->assertNull($refund->stripe_idempotency_key, 'A refund that was not sent has no key yet.');
        $this->assertNull($refund->stripe_refund_id);

        // A cash refund never carries a provider identity.
        (new RefundService())->approve($refund, $order->user->id, 'approved', 'cash');

        $refund = $refund->fresh();
        $this->assertSame('approved', $refund->status);
        $this->assertNull($refund->stripe_idempotency_key, 'A cash refund is not sent to a provider.');
        $this->assertNull($refund->stripe_refund_id);

        // The database refuses a second refund with the same key.
        $first = $this->refundFor($order, $order->user);
        $first->update(['stripe_idempotency_key' => 'refund-shared-key']);

        $second = $this->refundFor($order, $order->user);

        try {
            $second->update(['stripe_idempotency_key' => 'refund-shared-key']);
            $this->fail('The database must refuse two refunds with the same idempotency key.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertNotEmpty($e->errorInfo[1], 'The refusal must be a database error.');
        }

        // The database refuses a second refund that claims the same provider
        // refund.
        $first->update(['stripe_refund_id' => 're_provider_1']);

        try {
            $second->update(['stripe_refund_id' => 're_provider_1']);
            $this->fail('The database must refuse two refunds with the same provider refund.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertNotEmpty($e->errorInfo[1], 'The refusal must be a database error.');
        }

        // Many refunds without a key are allowed: the rows that were made
        // before the migration keep an empty value.
        $this->assertSame(
            2,
            Refund::query()->whereNull('stripe_idempotency_key')->count(),
            'Refunds without a key are allowed.'
        );
    }

    /**
     * The operator can repeat an approval that did not reach the provider.
     */
    public function test_the_admin_can_repeat_a_card_refund_that_was_not_confirmed(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'stripe');
        $order->update(['stripe_payment_intent_id' => 'pi_admin_1']);

        $refund = $this->refundFor($order, $order->user);

        $admin = User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);

        $gateway = new FakeStripeRefundGateway();
        $gateway->failure = new \RuntimeException('The provider refused the refund.');
        $this->app->instance(StripeRefundGateway::class, $gateway);

        $this->actingAs($admin)->post(route('admin.refunds.approve', $refund), [])
            ->assertRedirect(route('admin.refunds.show', $refund));

        $this->assertSame('approved', $refund->fresh()->status, 'A refused refund stays approved.');

        // The provider works now, so the repeated approval finishes it.
        $gateway->failure = null;

        $this->actingAs($admin)->post(route('admin.refunds.approve', $refund), [])
            ->assertRedirect(route('admin.refunds.show', $refund));

        $this->assertSame('completed', $refund->fresh()->status);
        $this->assertCount(1, $gateway->refunds, 'The provider must have made one refund, not two.');
        $this->assertSame(
            $gateway->calls[0]['key'],
            $gateway->calls[1]['key'],
            'The repeated approval must carry the same idempotency key.'
        );

        // A card refund the provider has not confirmed cannot be completed
        // by an operator.
        $pending = $this->refundFor($order, $order->user);
        $pending->update(['status' => 'approved', 'refund_method' => 'stripe']);

        $this->actingAs($admin)->post(route('admin.refunds.complete', $pending), [])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('approved', $pending->fresh()->status);
    }
}
