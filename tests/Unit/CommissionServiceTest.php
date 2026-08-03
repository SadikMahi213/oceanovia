<?php

namespace Tests\Unit;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_calculated_correctly(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create([
            'user_id' => $seller->id,
            'commission_rate' => 15.00,
            'status' => 'approved',
        ]);

        $buyer = User::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price' => 100.00,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'subtotal' => 100.00,
            'total' => 100.00,
            'status' => 'confirmed',
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_name' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
        ]);

        $service = new CommissionService();
        $commission = $service->calculateForItem($orderItem);

        $this->assertEquals(15.00, $commission);
    }
}
