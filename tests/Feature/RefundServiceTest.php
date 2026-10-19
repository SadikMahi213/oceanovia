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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
