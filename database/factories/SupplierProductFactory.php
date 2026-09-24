<?php

namespace Database\Factories;

use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierProductFactory extends Factory
{
    protected $model = SupplierProduct::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'supplier_id'     => User::factory(),
            'name'            => $name,
            'slug'            => Str::slug($name) . '-' . Str::random(6),
            'description'     => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'wholesale_price' => fake()->randomFloat(2, 5, 200),
            'sku'             => strtoupper(fake()->bothify('SP-####')),
            'status'          => 'published',
        ];
    }
}