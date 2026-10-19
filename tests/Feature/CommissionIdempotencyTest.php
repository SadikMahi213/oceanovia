<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function sellerWithRate(float $rate): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => $rate,
            'status'          => 'approved',
        ]);

        return $seller;
    }

    private function orderWithItems(User $seller, array $subtotals): Order
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $order = Order::create([
            'user_id'  => $buyer->id,
            'subtotal' => array_sum($subtotals),
            'total'    => array_sum($subtotals),
            'status'   => 'confirmed',
        ]);

        foreach ($subtotals as $subtotal) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'seller_id'    => $seller->id,
                'product_name' => 'Test Product',
                'quantity'     => 1,
                'unit_price'   => $subtotal,
                'subtotal'     => $subtotal,
            ]);
        }

        return $order->fresh('items');
    }

    public function test_create_commissions_is_idempotent_across_repeated_calls(): void
    {
        $seller = $this->sellerWithRate(10.00);
        $order = $this->orderWithItems($seller, [100.00, 50.00]);
        $service = new CommissionService();

        $service->createCommissions($order);
        $service->createCommissions($order);
        $service->createCommissions($order);

        $this->assertSame(2, Commission::count());

        foreach ($order->items as $item) {
            $this->assertSame(1, Commission::where('order_item_id', $item->id)->count());
        }
    }

    public function test_commission_amount_matches_seller_rate(): void
    {
        $seller = $this->sellerWithRate(15.00);
        $order = $this->orderWithItems($seller, [100.00]);

        (new CommissionService())->createCommissions($order);

        $commission = Commission::firstOrFail();
        $this->assertEquals(15.00, (float) $commission->amount);
        $this->assertEquals(15.00, (float) $commission->rate);
    }

    public function test_partial_creation_backfills_missing_commissions(): void
    {
        $seller = $this->sellerWithRate(10.00);
        $order = $this->orderWithItems($seller, [100.00, 50.00]);
        $service = new CommissionService();

        $firstItem = $order->items->first();
        Commission::create([
            'order_id'      => $order->id,
            'order_item_id' => $firstItem->id,
            'seller_id'     => $seller->id,
            'rate'          => 10.00,
            'subtotal'      => $firstItem->subtotal,
            'amount'        => round((float) $firstItem->subtotal * 0.10, 2),
            'status'        => 'pending',
        ]);

        $service->createCommissions($order);

        $this->assertSame(2, Commission::count());
    }
}
