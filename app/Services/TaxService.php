<?php

namespace App\Services;

use App\Models\TaxRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TaxService
{
    public function getRate(string $stateCode): float
    {
        $stateCode = strtoupper($stateCode);

        return Cache::remember("tax_rate_{$stateCode}", 86400, function () use ($stateCode) {
            $rate = TaxRate::active()->byState($stateCode)->first();

            return $rate ? (float) $rate->rate : 0.0;
        });
    }

    public function calculate(float $subtotal, string $stateCode): array
    {
        $rate = $this->getRate($stateCode);

        $taxRate = TaxRate::active()->byState($stateCode)->first();

        return [
            'rate'   => $rate,
            'amount' => round($subtotal * $rate, 2),
            'name'   => $taxRate?->name ?? 'No Tax',
        ];
    }

    public function getAllRates(): Collection
    {
        return TaxRate::active()->get();
    }
}
