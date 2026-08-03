<?php

namespace Database\Factories;

use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierProfileFactory extends Factory
{
    protected $model = SupplierProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'company_slug' => Str::slug(fake()->company()),
            'status' => 'approved',
        ];
    }
}
