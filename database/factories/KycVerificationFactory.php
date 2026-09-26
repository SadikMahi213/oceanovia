<?php

namespace Database\Factories;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycVerificationFactory extends Factory
{
    protected $model = KycVerification::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'document_type'   => 'national_id',
            'document_number' => (string) fake()->numerify('##########'),
            'document_front'  => 'kyc/front.jpg',
            'document_back'   => 'kyc/back.jpg',
            'selfie'          => 'kyc/selfie.jpg',
            'status'          => 'pending',
        ];
    }
}