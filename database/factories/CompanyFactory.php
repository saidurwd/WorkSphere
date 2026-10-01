<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_code' => strtoupper(fake()->unique()->bothify('CO-####')),
            'company_name' => fake()->unique()->company(),
            'city' => fake()->city(),
            'country' => 'Bangladesh',
            'status' => 'active',
        ];
    }
}
