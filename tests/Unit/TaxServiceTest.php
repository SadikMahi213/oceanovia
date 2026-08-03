<?php

namespace Tests\Unit;

use App\Models\TaxRate;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_rate_for_california(): void
    {
        TaxRate::where('state_code', 'CA')->update(['rate' => 0.0882]);

        $service = new TaxService();
        $rate = $service->getRate('CA');

        $this->assertEquals(0.0882, $rate);
    }

    public function test_tax_rate_for_unknown_state_returns_zero(): void
    {
        $service = new TaxService();
        $rate = $service->getRate('XX');

        $this->assertEquals(0.0, $rate);
    }

    public function test_tax_calculation(): void
    {
        TaxRate::where('state_code', 'TX')->update(['rate' => 0.0820, 'name' => 'Texas']);

        $service = new TaxService();
        $result = $service->calculate(100.00, 'TX');

        $this->assertEquals(0.0820, $result['rate']);
        $this->assertEquals(8.20, $result['amount']);
        $this->assertEquals('Texas', $result['name']);
    }
}
