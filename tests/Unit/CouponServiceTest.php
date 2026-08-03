<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentage_coupon_discount(): void
    {
        $coupon = Coupon::create([
            'code' => 'PERCENT10',
            'type' => 'percentage',
            'value' => 10.00,
            'is_active' => true,
        ]);

        $service = new CouponService();
        $discount = $service->calculateDiscount($coupon, 200.00);

        $this->assertEquals(20.00, $discount);
    }

    public function test_fixed_coupon_discount(): void
    {
        $coupon = Coupon::create([
            'code' => 'FIXED50',
            'type' => 'fixed',
            'value' => 50.00,
            'is_active' => true,
        ]);

        $service = new CouponService();
        $discount = $service->calculateDiscount($coupon, 200.00);

        $this->assertEquals(50.00, $discount);
    }

    public function test_expired_coupon_invalid(): void
    {
        Coupon::create([
            'code' => 'EXPIRED',
            'type' => 'percentage',
            'value' => 20.00,
            'is_active' => true,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDays(1),
        ]);

        $service = new CouponService();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->validate('EXPIRED');
    }
}
