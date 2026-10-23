<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes an order's totals from its active (non-held, non-cancelled) items.
 *
 * Shipping, tax, discount and the total are derived by exactly one code path, so
 * creation, confirmation and decline of a stock-held item cannot drift. Held
 * items carry a zero subtotal and are excluded from the active set.
 */
class OrderTotalsService
{
    public function __construct(
        private readonly ShippingService $shipping,
        private readonly TaxService $tax,
        private readonly CouponService $coupons,
    ) {}

    public function recompute(Order $order): void
    {
        $order->loadMissing(['items.product.inventory', 'items.product.seller', 'shippingAddress']);

        $activeItems = $order->items->reject(fn (OrderItem $item) => in_array($item->status, ['cancelled', 'returned', OrderItem::STATUS_AWAITING_STOCK], true));

        $subtotal = round((float) $activeItems->sum(fn (OrderItem $i) => (float) $i->subtotal), 2);

        // Build a transient cart so the existing shipping calculation (vendor
        // grouping, free-shipping thresholds, supplier zones) keeps working.
        $cart = new Cart();
        $cart->setRelation('items', $activeItems->map(function (OrderItem $item) {
            $cartItem = new CartItem();
            $cartItem->product_id = $item->product_id;
            $cartItem->quantity = (int) $item->quantity;
            $cartItem->unit_price = (float) $item->unit_price;
            $cartItem->setRelation('product', $item->product);

            return $cartItem;
        })->values());

        $shippingCost = $this->shipping->calculate($cart, $order->shippingAddress)['total'];

        $state = $order->shippingAddress?->state ?? '';
        $taxResult = $this->tax->calculate($subtotal, $state);
        $tax = (float) $taxResult['amount'];

        // Preserve an already-applied coupon, recomputing its discount on the
        // new subtotal and capping it so the total can never go negative.
        $discount = (float) $order->discount;
        $couponUsage = CouponUsage::where('order_id', $order->id)->first();

        if ($couponUsage !== null) {
            $coupon = Coupon::find($couponUsage->coupon_id);

            if ($coupon !== null) {
                $discount = $this->coupons->calculateDiscount($coupon, $subtotal);

                // If the subtotal dropped below the coupon's minimum, cap the
                // discount at the subtotal instead of dropping the coupon.
                if ($subtotal > 0 && $discount > $subtotal) {
                    $discount = $subtotal;
                }

                $couponUsage->update(['discount_amount' => $discount]);
            }
        }

        $total = round($subtotal + $shippingCost + $tax - $discount, 2);

        if ($total < 0) {
            $total = 0.0;
        }

        $order->update([
            'subtotal'      => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax'           => $tax,
            'discount'      => $discount,
            'total'         => $total,
        ]);
    }
}
