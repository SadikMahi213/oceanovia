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

class RefundReversalTest extends TestCase
{
    use RefreshDatabase;

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

    private function orderWithItems(User $seller, array $subtotals, string $paymentMethod = 'cod'): Order
    {
        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id'        => $buyer->id,
            'subtotal'       => array_sum($subtotals),
            'total'          => array_sum($subtotals),
            'status'         => 'delivered',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
        ]);

        foreach ($subtotals as $subtotal) {
            $product = Product::factory()->create(['seller_id' => $seller->id]);

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'seller_id'    => $seller->id,
                'product_name' => 'Product',
                'quantity'     => 1,
                'unit_price'   => $subtotal,
                'subtotal'     => $subtotal,
            ]);
        }

        return $order->fresh('items');
    }

    private function cashRefund(Order $order, float $amount, ?int $orderItemId = null): Refund
    {
        $buyer = $order->user;
        $refund = Refund::create([
            'order_id'      => $order->id,
            'order_item_id' => $orderItemId,
            'user_id'       => $buyer->id,
            'amount'        => $amount,
            'reason'        => 'Test refund',
            'status'        => 'pending',
        ]);

        $service = new RefundService();
        $service->approve($refund, $buyer->id, 'approved', 'cash');
        $service->complete($refund->fresh(), $buyer->id);

        return $refund->fresh();
    }

    public function test_a_partial_refund_reverses_only_the_covered_share(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00]);
        (new CommissionService())->createCommissions($order);

        // The seller is credited the net (90.00).
        (new PayoutService())->credit($seller->id, 90.00);

        // Refund only half of the item: half of the net must come back.
        $this->cashRefund($order, 50.00, $order->items->first()->id);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame(45.00, (float) $balance->balance, 'Half of the 90.00 net must be reversed.');
        $this->assertSame(45.00, (float) $balance->total_earned);

        // The item is not fully refunded, so the commission stays pending.
        $this->assertSame('pending', Commission::firstOrFail()->status);
        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_a_second_refund_of_the_same_item_never_over_reverses(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00]);
        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 90.00);

        $itemId = $order->items->first()->id;

        $this->cashRefund($order, 50.00, $itemId);
        $this->cashRefund($order, 50.00, $itemId);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame(0.00, (float) $balance->balance, 'Reversing the full item must empty the credit, not go negative.');
        $this->assertGreaterThanOrEqual(0, (float) $balance->balance);

        // The item is now fully refunded, so the commission is reversed.
        $this->assertSame('reversed', Commission::firstOrFail()->status);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame(
            2,
            RefundReversal::where('order_item_id', $itemId)->count(),
            'Each finalized refund must record its own reversal row.'
        );
    }

    public function test_a_partial_refund_does_not_mark_a_multi_item_order_refunded(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00, 100.00]);
        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 180.00);

        // Refund only the first item in full.
        $this->cashRefund($order, 100.00, $order->items->first()->id);

        $this->assertSame('delivered', $order->fresh()->status, 'One refunded item must not refund the whole order.');

        $commissions = Commission::orderBy('id')->get();
        $this->assertSame('reversed', $commissions[0]->status);
        $this->assertSame('pending', $commissions[1]->status, 'The other item is still outstanding.');
    }

    public function test_refunding_every_item_marks_the_order_refunded(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00, 50.00]);
        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 135.00);

        $this->cashRefund($order, 100.00, $order->items[0]->id);
        $this->cashRefund($order, 50.00, $order->items[1]->id);

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame(0, Commission::where('status', '!=', 'reversed')->count());
    }

    public function test_order_level_refund_is_allocated_across_items_and_reverses_each_net(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00, 60.00]);
        (new CommissionService())->createCommissions($order);

        $net = 90.00 + 54.00;
        (new PayoutService())->credit($seller->id, $net);

        $this->cashRefund($order, 160.00);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertSame(0.00, (float) $balance->balance);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame(2, RefundReversal::count());
        $this->assertSame(0, Commission::where('status', '!=', 'reversed')->count());
    }

    public function test_a_refund_reversal_is_ledgered_as_a_debit(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00]);
        (new CommissionService())->createCommissions($order);
        (new PayoutService())->credit($seller->id, 90.00);

        $refund = $this->cashRefund($order, 100.00, $order->items->first()->id);

        $debit = Transaction::where('type', 'debit')
            ->where('reference_type', Refund::class)
            ->where('reference_id', $refund->id)
            ->first();

        $this->assertNotNull($debit, 'The reversal must be recorded on the ledger against the refund.');
        $this->assertSame(90.00, (float) $debit->amount);
    }

    public function test_a_refund_amount_above_the_remaining_refundable_is_refused(): void
    {
        $seller = $this->seller();
        $order = $this->orderWithItems($seller, [100.00]);

        $this->cashRefund($order, 60.00, $order->items->first()->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        (new RefundService())->requestRefund(
            $order->fresh('items'),
            $order->user,
            'Second refund',
            60.00,
            $order->items->first()->id,
        );
    }

    public function test_an_order_item_from_another_order_is_rejected(): void
    {
        $seller = $this->seller();
        $first = $this->orderWithItems($seller, [100.00]);
        $second = $this->orderWithItems($seller, [100.00]);

        $foreignItemId = $second->items->first()->id;

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        (new RefundService())->requestRefund(
            $first,
            $first->user,
            'Cross order refund',
            50.00,
            $foreignItemId,
        );
    }
}
