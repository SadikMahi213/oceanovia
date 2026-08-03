<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function validate(string $code, ?User $user = null, ?float $subtotal = null): Coupon
    {
        $coupon = Coupon::valid()->where('code', $code)->first();

        if (!$coupon) {
            throw ValidationException::withMessages(['coupon_code' => 'Invalid or expired coupon code.']);
        }

        if ($subtotal !== null && $coupon->min_order_amount > 0 && $subtotal < $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => "Minimum subtotal of \${$coupon->min_order_amount} is required.",
            ]);
        }

        if ($user !== null && $coupon->per_user_limit > 0) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userUsage >= $coupon->per_user_limit) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'You have already used this coupon the maximum number of times.',
                ]);
            }
        }

        return $coupon;
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = round($subtotal * $coupon->value / 100, 2);
        } else {
            $discount = min((float) $coupon->value, $subtotal);
        }

        // Enforce the max_discount cap
        if ($coupon->max_discount > 0) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(max($discount, 0), 2);
    }

    public function apply(Coupon $coupon, Order $order, User $user): void
    {
        $discount = $this->calculateDiscount($coupon, $order->subtotal);

        CouponUsage::create([
            'coupon_id'       => $coupon->id,
            'user_id'         => $user->id,
            'order_id'        => $order->id,
            'discount_amount' => $discount,
        ]);

        $coupon->increment('used_count');

        $order->update([
            'discount' => $discount,
            'total'    => $order->subtotal + $order->shipping_cost + $order->tax - $discount,
        ]);
    }
}
