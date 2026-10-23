<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundReversal;
use App\Models\SellerBalance;
use App\Models\SellerProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PayoutService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRefundReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function stripeOrder(User $seller, float $subtotal = 100.00): Order
    {
        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

        $buyer = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $order = Order::create([
            'user_id'                  => $buyer->id,
            'subtotal'                 => $subtotal,
            'total'                    => $subtotal,
            'status'                   => 'delivered',
            'payment_method'           => 'stripe',
            'payment_status'           => 'paid',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => 'Product',
            'quantity'     => 1,
            'unit_price'   => $subtotal,
            'subtotal'     => $subtotal,
        ]);

        return $order->fresh('items');
    }

    public function test_a_provider_refund_without_a_local_refund_creates_and_finalizes_one(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->stripeOrder($seller, 100.00);

        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 90.00);

        (new RefundService())->recordProviderRefund('re_provider_1', 'pi_test_123', 10000, 'usd');

        $refund = Refund::firstOrFail();
        $this->assertSame('completed', $refund->status);
        $this->assertSame('re_provider_1', $refund->stripe_refund_id);
        $this->assertSame(100.00, (float) $refund->amount);
        $this->assertSame('refunded', $order->fresh()->status);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame(0.00, (float) $balance->balance);
        $this->assertSame('reversed', Commission::firstOrFail()->status);
        $this->assertSame(1, RefundReversal::count());
        $this->assertSame(
            1,
            Transaction::where('type', 'debit')->where('reference_type', Refund::class)->count()
        );
    }

    public function test_a_repeated_provider_refund_is_reconciled_once(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->stripeOrder($seller, 100.00);

        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 90.00);

        $service = new RefundService();
        $service->recordProviderRefund('re_provider_1', 'pi_test_123', 10000, 'usd');
        $service->recordProviderRefund('re_provider_1', 'pi_test_123', 10000, 'usd');

        $this->assertSame(1, Refund::count());
        $this->assertSame(1, RefundReversal::count());
        $this->assertSame(
            1,
            Transaction::where('type', 'debit')->where('reference_type', Refund::class)->count()
        );

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame(0.00, (float) $balance->balance);
    }

    public function test_a_provider_refund_resumes_a_pending_local_refund(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $order = $this->stripeOrder($seller, 100.00);

        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 90.00);

        // A local refund request exists but was never sent to the provider.
        $local = Refund::create([
            'order_id' => $order->id,
            'user_id'  => $order->user_id,
            'amount'   => 100.00,
            'reason'   => 'Customer request',
            'status'   => 'pending',
        ]);

        (new RefundService())->recordProviderRefund('re_provider_late', 'pi_test_123', 10000, 'usd');

        $local = $local->fresh();
        $this->assertSame('completed', $local->status);
        $this->assertSame('re_provider_late', $local->stripe_refund_id);
        $this->assertSame(1, Refund::count(), 'The existing local refund must be reused.');
        $this->assertSame('refunded', $order->fresh()->status);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame(0.00, (float) $balance->balance);
    }

    public function test_a_provider_refund_for_an_unknown_order_is_ignored(): void
    {
        (new RefundService())->recordProviderRefund('re_unknown', 'pi_missing', 5000, 'usd');

        $this->assertSame(0, Refund::count());
    }
}
