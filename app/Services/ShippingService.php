<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\ShippingMethod;
use App\Models\SupplierShippingZone;
use Illuminate\Support\Collection;

class ShippingService
{
    /**
     * Calculate shipping for a cart, grouped by vendor (multivendor).
     * Returns total shipping cost and per-vendor breakdown.
     */
    public function calculate(Cart $cart, ?Address $address = null): array
    {
        if ($cart->items->isEmpty()) {
            return ['total' => 0.0, 'breakdown' => []];
        }

        // Eager load needed relations if not already loaded
        $cart->loadMissing(['items.product.inventory', 'items.product.seller']);

        $groups = $cart->items->groupBy(function ($item) {
            // Prefer supplier_id if present (supplied product), else seller_id
            $supplierId = $item->product->inventory?->supplier_id;
            if ($supplierId) {
                return 'supplier_'.$supplierId;
            }

            return 'seller_'.$item->product->seller_id;
        });

        $total = 0.0;
        $breakdown = [];

        foreach ($groups as $key => $items) {
            $vendorSubtotal = $items->sum(fn ($i) => (float) $i->unit_price * (int) $i->quantity);
            $vendorWeight = $items->sum(fn ($i) => (float) ($i->product->weight ?? 0) * (int) $i->quantity);

            $cost = $this->calculateForVendor($key, $items, $vendorSubtotal, $vendorWeight, $address);

            $breakdown[$key] = [
                'vendor' => $key,
                'subtotal' => round($vendorSubtotal, 2),
                'weight' => round($vendorWeight, 2),
                'shipping' => round($cost, 2),
                'items' => $items->count(),
            ];

            $total += $cost;
        }

        return [
            'total' => round($total, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate shipping for a single vendor group.
     */
    protected function calculateForVendor(string $vendorKey, Collection $items, float $subtotal, float $weight, ?Address $address): float
    {
        // Extract supplier id if this group is supplier-based
        $supplierId = null;
        if (str_starts_with($vendorKey, 'supplier_')) {
            $supplierId = (int) str_replace('supplier_', '', $vendorKey);
        }

        // 1) Try supplier-specific zone/rate if applicable
        if ($supplierId) {
            $rate = $this->findSupplierRate($supplierId, $address, $subtotal, $weight);
            if ($rate !== null) {
                return (float) $rate;
            }
        }

        // 2) Fallback to global ShippingMethod
        $method = ShippingMethod::active()->ordered()->first();
        if ($method) {
            // Respect free shipping threshold per vendor subtotal
            if ($method->free_shipping_threshold !== null && $subtotal >= (float) $method->free_shipping_threshold) {
                return 0.0;
            }

            $base = (float) $method->base_rate;
            $perKg = (float) $method->rate_per_kg;

            return round($base + ($perKg * $weight), 2);
        }

        // 3) Ultimate fallback: simple rule matching ShippingController mock
        // Free over $50 per vendor, else $5.99 + $3 heavy surcharge if weight >5
        $heavy = $weight > 5 ? 3.00 : 0;

        if ($subtotal >= 50) {
            return round(0 + $heavy, 2);
        }

        return round(5.99 + $heavy, 2);
    }

    protected function findSupplierRate(int $supplierId, ?Address $address, float $subtotal, float $weight): ?float
    {
        $zones = SupplierShippingZone::where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->with(['rates' => fn ($q) => $q->where('is_active', true)->orderBy('rate')])
            ->get();

        if ($zones->isEmpty()) {
            return null;
        }

        // Find first zone that matches the destination
        $matchedZone = null;
        foreach ($zones as $zone) {
            if ($this->zoneMatchesAddress($zone, $address)) {
                $matchedZone = $zone;
                break;
            }
        }

        // If no zone matches and we have an address, try a global zone (empty countries/states)
        if (! $matchedZone && $address === null) {
            $matchedZone = $zones->first();
        }

        if (! $matchedZone) {
            return null;
        }

        foreach ($matchedZone->rates as $rate) {
            $minW = $rate->min_weight !== null ? (float) $rate->min_weight : null;
            $maxW = $rate->max_weight !== null ? (float) $rate->max_weight : null;
            $minT = $rate->min_order_total !== null ? (float) $rate->min_order_total : null;
            $maxT = $rate->max_order_total !== null ? (float) $rate->max_order_total : null;

            if ($minW !== null && $weight < $minW) {
                continue;
            }
            if ($maxW !== null && $weight > $maxW) {
                continue;
            }
            if ($minT !== null && $subtotal < $minT) {
                continue;
            }
            if ($maxT !== null && $subtotal > $maxT) {
                continue;
            }

            // Type handling
            if ($rate->type === 'free') {
                return 0.0;
            }

            // For flat, weight_based, order_total_based we use the rate as configured
            // weight_based could be rate * weight, but existing data uses flat rate per zone
            return (float) $rate->rate;
        }

        return null;
    }

    protected function zoneMatchesAddress(SupplierShippingZone $zone, ?Address $address): bool
    {
        if (! $address) {
            // Without address, only match zones that are effectively global (no restrictions)
            return empty($zone->countries) && empty($zone->states) && empty($zone->cities) && empty($zone->zip_codes);
        }

        // Countries check
        if (! empty($zone->countries)) {
            $countries = array_map('strtoupper', $zone->countries);
            if (! in_array(strtoupper($address->country), $countries, true)) {
                return false;
            }
        }

        // States check
        if (! empty($zone->states)) {
            $states = array_map('strtoupper', $zone->states);
            if (! in_array(strtoupper($address->state), $states, true)) {
                return false;
            }
        }

        // Cities check
        if (! empty($zone->cities)) {
            $cities = array_map('strtoupper', $zone->cities);
            if (! in_array(strtoupper($address->city), $cities, true)) {
                return false;
            }
        }

        // Zip check
        if (! empty($zone->zip_codes)) {
            if (! in_array($address->zip, $zone->zip_codes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Simple helper for cart page estimate (no address).
     */
    public function estimateForCart(Cart $cart): float
    {
        return $this->calculate($cart, null)['total'];
    }
}
