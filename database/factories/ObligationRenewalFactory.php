<?php

namespace Database\Factories;

use App\Models\Obligation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObligationRenewalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'obligation_id' => Obligation::factory(),
            'previous_expiry_date' => fake()->date(),
            'new_start_date' => fake()->date(),
            'new_expiry_date' => fake()->date(),
            'renewal_date' => fake()->date(),
            'vendor_id' => null,
            'cost' => fake()->randomFloat(2, 1000, 50000),
            'currency' => 'BDT',
            'purchase_reference' => null,
            'invoice_reference' => null,
            'remarks' => null,
            'renewed_by' => User::factory(),
        ];
    }
}
