<?php

namespace Tests\Feature;

use App\Exceptions\RefundStateException;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\SellerBalance;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CommissionService;
use App\Services\PayoutService;
use App\Services\RefundService;
use App\Services\StripeRefundGateway;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\FailingAuditService;
use Tests\Support\FakeStripeRefundGateway;
use Tests\TestCase;

/**
 * The card refund must be made exactly once, even when the operation is
 * repeated, retried or interrupted.
 *
 * The payment provider call happens outside the database transaction, so the
 * database has to remember which refund was already sent. The idempotency
 * key of the refund is what makes a repeated call safe: the provider replays
 * the refund it already created for that key instead of creating a second
 * one.
 *
 * The provider itself is replaced by a fake here. The fake keeps the
 * behaviour that matters - the same key always returns the same refund, a
 * different key creates a new one - and the real provider is not exercised by
 * this suite.
 */
class RefundIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function orderForSeller(User $seller, float $subtotal, string $paymentMethod = 'cod', ?string $paymentIntentId = null): Order
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $order = Order::create([
            'user_id'                => $buyer->id,
            'subtotal'               => $subtotal,
            'total'                  => $subtotal,
            'status'                 => 'delivered',
            'payment_method'         => $paymentMethod,
            'payment_status'         => 'pending',
            'stripe_payment_intent_id' => $paymentIntentId,
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

    private function refundFor(Order $order, User $buyer, ?float $amount = null): Refund
    {
        return Refund::create([
            'order_id' => $order->id,
            'user_id'  => $buyer->id,
            'amount'   => $amount ?? $order->total,
            'reason'   => 'Test refund',
            'status'   => 'pending',
        ]);
    }

    /**
     * A card order with a credited seller and a recorded commission.
     */
    private function cardRefundScenario(float $subtotal = 100.00): array
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, $subtotal, 'stripe', 'pi_test_123');

        (new CommissionService())->createCommissions($order);

        $commissionService = new CommissionService();
        $net = $commissionService->netForItem($order->items->first());

        (new PayoutService())->credit($seller->id, $net);

        $refund = $this->refundFor($order, $order->user);

        return [$seller, $order, $refund, $net];
    }

    private function fakeGateway(?RuntimeException $failure = null): FakeStripeRefundGateway
    {
        $gateway = new FakeStripeRefundGateway();
        $gateway->failure = $failure;

        $this->app->instance(StripeRefundGateway::class, $gateway);

        return $gateway;
    }

    public function test_a_successful_card_refund_is_finalized_exactly_once(): void
    {
        [$seller, $order, $refund, $net] = $this->cardRefundScenario();

        $gateway = $this->fakeGateway();

        (new RefundService())->approve($refund, $order->user->id, 'approved');

        $refund = $refund->fresh();

        $this->assertSame('completed', $refund->status, 'A confirmed card refund must be completed.');
        $this->assertNotEmpty($refund->stripe_refund_id, 'The provider refund must be recorded.');
        $this->assertNotNull($refund->stripe_refunded_at, 'The moment the provider confirmed must be recorded.');
        $this->assertNotEmpty($refund->stripe_idempotency_key, 'The refund must carry its idempotency key.');

        $this->assertCount(1, $gateway->calls, 'The provider must be called once.');
        $this->assertSame('pi_test_123', $gateway->calls[0]['intent']);
        $this->assertSame(10000, $gateway->calls[0]['amount'], 'The amount must be sent in cents.');

        $this->assertSame('refunded', $order->fresh()->status, 'The order must be marked refunded.');
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('reversed', Commission::firstOrFail()->status, 'The commission must be reversed.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $balance->balance, 'The credited amount must be taken back exactly once.');
        $this->assertEquals(0.00, (float) $balance->total_earned);
    }

    public function test_a_repeated_attempt_reuses_the_same_idempotency_key(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        // The first attempt does not reach the provider, so the refund stays
        // approved and can be attempted again.
        $gateway = $this->fakeGateway(new RuntimeException('The provider refused the refund.'));
        $gateway->failure = null;

        $service = new RefundService();

        $gateway->failure = new RuntimeException('The provider refused the refund.');
        $service->approve($refund, $order->user->id, 'approved');

        $key = (string) $refund->fresh()->stripe_idempotency_key;
        $this->assertNotEmpty($key, 'The failed attempt must still record the key.');

        // The retry, as an operator would trigger it.
        $gateway->failure = null;
        $service->approve($refund->fresh(), $order->user->id, 'approved');

        $this->assertCount(2, $gateway->calls, 'The attempt is repeated.');
        $this->assertSame($key, $gateway->calls[0]['key'], 'The first attempt carries the key.');
        $this->assertSame($key, $gateway->calls[1]['key'], 'The repeated attempt must carry the same key.');
        $this->assertCount(1, $gateway->refunds, 'The provider must have made one refund, not two.');

        $refund = $refund->fresh();
        $this->assertSame('completed', $refund->status, 'The retry must finish the refund.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $balance->balance, 'The credit must be taken back exactly once.');
    }

    public function test_a_completed_refund_is_never_sent_to_the_provider_again(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        $gateway = $this->fakeGateway();

        $service = new RefundService();
        $service->approve($refund, $order->user->id, 'approved');

        $this->assertSame('completed', $refund->fresh()->status);

        // A repeated approval of a refund that is already completed.
        $service->approve($refund->fresh(), $order->user->id, 'approved');

        $this->assertCount(1, $gateway->calls, 'A completed refund must not be sent again.');
        $this->assertCount(1, $gateway->refunds, 'The provider must have made one refund, not two.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $balance->balance, 'The credit must be taken back exactly once.');
    }

    public function test_a_stripe_error_does_not_produce_a_false_local_success(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        $gateway = $this->fakeGateway(new RuntimeException('The provider refused the refund.'));

        (new RefundService())->approve($refund, $order->user->id, 'approved');

        $refund = $refund->fresh();

        $this->assertSame('approved', $refund->status, 'A refused refund must not be completed.');
        $this->assertNull($refund->stripe_refund_id, 'A refused refund must not carry a provider refund id.');
        $this->assertNull($refund->stripe_refunded_at);

        $this->assertSame('delivered', $order->fresh()->status, 'The order must not be marked refunded.');
        $this->assertNotSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('pending', Commission::firstOrFail()->status, 'The commission must not be reversed.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(90.00, (float) $balance->balance, 'The credit must not be touched.');
        $this->assertCount(1, $gateway->calls);
    }

    public function test_a_recovery_after_a_failed_finalization_does_not_refund_again(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        $gateway = $this->fakeGateway();

        // The provider refunds, but the local finalization fails.
        $this->app->instance(AuditService::class, new FailingAuditService());

        try {
            (new RefundService())->approve($refund, $order->user->id, 'approved');
            $this->fail('The failed finalization must be reported.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('audit log', $e->getMessage());
        }

        $refund = $refund->fresh();

        $this->assertSame(
            RefundService::STATUS_STRIPE_REFUNDED,
            $refund->status,
            'The refund must remember that the provider already refunded.'
        );
        $this->assertNotEmpty($refund->stripe_refund_id);
        $this->assertSame('delivered', $order->fresh()->status, 'The order is not refunded yet.');
        $this->assertCount(1, $gateway->calls, 'The provider was called once.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(90.00, (float) $balance->balance, 'The credit was not taken back.');

        // The recovery: the audit log works again and the refund is approved
        // a second time.
        $this->app->instance(AuditService::class, new AuditService());

        (new RefundService())->approve($refund->fresh(), $order->user->id, 'approved');

        $refund = $refund->fresh();

        $this->assertSame('completed', $refund->status, 'The recovery must finish the refund.');
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertCount(1, $gateway->calls, 'The recovery must not call the provider again.');
        $this->assertCount(1, $gateway->refunds, 'The provider must have made one refund, not two.');

        $balance = $balance->fresh();
        $this->assertEquals(0.00, (float) $balance->balance, 'The credit must be taken back exactly once.');
    }

    public function test_two_approvals_of_the_same_refund_create_one_logical_operation(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        $gateway = $this->fakeGateway();

        $service = new RefundService();
        $service->approve($refund, $order->user->id, 'approved');
        $service->approve($refund->fresh(), $order->user->id, 'approved');

        $this->assertSame(1, Refund::query()->count(), 'There must be one refund, not two.');
        $this->assertCount(1, $gateway->refunds, 'The provider must have made one refund, not two.');

        $key = (string) $refund->fresh()->stripe_idempotency_key;

        // The database refuses a second refund that claims the same key.
        $this->expectException(QueryException::class);

        Refund::query()->create([
            'order_id'              => $order->id,
            'user_id'               => $order->user_id,
            'amount'                => 10.00,
            'reason'                => 'A second refund with the same key',
            'status'                => 'pending',
            'stripe_idempotency_key' => $key,
        ]);
    }

    public function test_different_refunds_receive_different_idempotency_keys(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        $otherOrder = $this->orderForSeller($seller, 40.00, 'stripe', 'pi_test_456');
        $otherRefund = $this->refundFor($otherOrder, $otherOrder->user);

        $gateway = $this->fakeGateway();

        $service = new RefundService();
        $service->approve($refund, $order->user->id, 'approved');
        $service->approve($otherRefund, $otherOrder->user->id, 'approved');

        $first = (string) $refund->fresh()->stripe_idempotency_key;
        $second = (string) $otherRefund->fresh()->stripe_idempotency_key;

        $this->assertNotEmpty($first);
        $this->assertNotEmpty($second);
        $this->assertNotSame($first, $second, 'Two refunds must not share an idempotency key.');
        $this->assertCount(2, $gateway->refunds, 'The provider must have made two refunds.');
        $this->assertSame('pi_test_123', $gateway->calls[0]['intent']);
        $this->assertSame('pi_test_456', $gateway->calls[1]['intent']);
    }

    public function test_invalid_state_transitions_are_rejected(): void
    {
        [$seller, $order, $refund] = $this->cardRefundScenario();

        $this->fakeGateway();

        $service = new RefundService();

        // A rejected refund cannot be approved.
        $refund->update(['status' => 'rejected']);

        try {
            $service->approve($refund->fresh(), $order->user->id, 'approved');
            $this->fail('A rejected refund must not be approvable.');
        } catch (RefundStateException $e) {
            $this->assertStringContainsString('rejected', $e->getMessage());
        }

        // A pending refund cannot be completed.
        $pending = $this->refundFor($order, $order->user);

        try {
            $service->complete($pending, $order->user->id);
            $this->fail('A pending refund must not be completable.');
        } catch (RefundStateException $e) {
            $this->assertStringContainsString('pending', $e->getMessage());
        }

        // A card refund the provider has not confirmed cannot be completed.
        $cardRefund = $this->refundFor($order, $order->user);
        $cardRefund->update(['status' => 'approved', 'refund_method' => 'stripe']);

        try {
            $service->complete($cardRefund->fresh(), $order->user->id);
            $this->fail('A card refund without a provider refund must not be completable.');
        } catch (RefundStateException $e) {
            $this->assertStringContainsString('provider', $e->getMessage());
        }

        // A completed refund cannot be rejected.
        $completed = $this->refundFor($order, $order->user);
        $completed->update(['status' => 'completed']);

        try {
            $service->reject($completed->fresh(), $order->user->id, 'no');
            $this->fail('A completed refund must not be rejectable.');
        } catch (RefundStateException $e) {
            $this->assertStringContainsString('completed', $e->getMessage());
        }
    }

    public function test_a_refund_without_a_payment_intent_is_never_sent(): void
    {
        [$seller, $order] = $this->cardRefundScenario();

        $order->update(['stripe_payment_intent_id' => null]);

        $gateway = $this->fakeGateway();

        $refund = $this->refundFor($order->fresh(), $order->user);

        (new RefundService())->approve($refund, $order->user->id, 'approved');

        $this->assertCount(0, $gateway->calls, 'Without a payment reference no refund may be sent.');
        $this->assertSame('approved', $refund->fresh()->status);
        $this->assertNull($refund->fresh()->stripe_refund_id);
    }

    public function test_the_refund_request_rules_are_unchanged(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->orderForSeller($seller, 100.00, 'cod');

        $service = new RefundService();

        // An order that is not delivered cannot be refunded.
        $order->update(['status' => 'pending']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $service->requestRefund($order->fresh(), $order->user, 'Because');
    }
}
