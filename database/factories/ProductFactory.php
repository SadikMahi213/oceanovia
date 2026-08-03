<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'slug' => Str::slug(fake()->words(3, true)) . '-' . Str::random(6),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 200),
            'status' => 'published',
            'is_featured' => false,
        ];
    }
}
