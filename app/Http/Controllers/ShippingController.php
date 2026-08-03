<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function rates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_zip' => ['required', 'string', 'size:5'],
            'weight'          => ['required', 'numeric', 'min:0.1'],
            'width'           => ['nullable', 'numeric', 'min:0'],
            'height'          => ['nullable', 'numeric', 'min:0'],
            'length'          => ['nullable', 'numeric', 'min:0'],
            'subtotal'        => ['nullable', 'numeric', 'min:0'],
        ]);

        $weight = (float) $validated['weight'];
        $subtotal = (float) ($validated['subtotal'] ?? 0);

        $heavySurcharge = $weight > 5 ? 3.00 : 0;

        if ($subtotal >= 50) {
            $rates = [
                [
                    'carrier'  => 'USPS',
                    'service'  => 'Standard',
                    'cost'     => round(0 + $heavySurcharge, 2),
                    'estimate' => '3-5 business days',
                ],
                [
                    'carrier'  => 'UPS',
                    'service'  => 'Standard',
                    'cost'     => round(0 + $heavySurcharge, 2),
                    'estimate' => '2-4 business days',
                ],
                [
                    'carrier'  => 'FedEx',
                    'service'  => 'Standard',
                    'cost'     => round(0 + $heavySurcharge, 2),
                    'estimate' => '2-4 business days',
                ],
                [
                    'carrier'  => 'UPS',
                    'service'  => 'Expedited',
                    'cost'     => round(9.99 + $heavySurcharge, 2),
                    'estimate' => '1-2 business days',
                ],
                [
                    'carrier'  => 'FedEx',
                    'service'  => 'Expedited',
                    'cost'     => round(9.99 + $heavySurcharge, 2),
                    'estimate' => '1-2 business days',
                ],
            ];
        } else {
            $rates = [
                [
                    'carrier'  => 'USPS',
                    'service'  => 'Standard',
                    'cost'     => round(5.99 + $heavySurcharge, 2),
                    'estimate' => '3-5 business days',
                ],
                [
                    'carrier'  => 'UPS',
                    'service'  => 'Standard',
                    'cost'     => round(5.99 + $heavySurcharge, 2),
                    'estimate' => '2-4 business days',
                ],
                [
                    'carrier'  => 'FedEx',
                    'service'  => 'Standard',
                    'cost'     => round(5.99 + $heavySurcharge, 2),
                    'estimate' => '2-4 business days',
                ],
                [
                    'carrier'  => 'UPS',
                    'service'  => 'Expedited',
                    'cost'     => round(12.99 + $heavySurcharge, 2),
                    'estimate' => '1-2 business days',
                ],
                [
                    'carrier'  => 'FedEx',
                    'service'  => 'Expedited',
                    'cost'     => round(12.99 + $heavySurcharge, 2),
                    'estimate' => '1-2 business days',
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'rates'   => $rates,
        ]);
    }
}
