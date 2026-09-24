<?php

namespace Database\Factories;

use App\Models\SupplierProduct;
use App\Models\SupplierProductStock;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierProductStockFactory extends Factory
{
    protected $model = SupplierProductStock::class;

    public function definition(): array
    {
        return [
            'supplier_product_id'   => SupplierProduct::factory(),
            'stock_quantity'        => fake()->numberBetween(10, 200),
            'reserved_quantity'     => 0,
            'sold_quantity'         => 0,
            'stock_alert_threshold' => 5,
        ];
    }
}