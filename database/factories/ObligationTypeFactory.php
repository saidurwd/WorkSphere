<?php

namespace Database\Factories;

use App\Enums\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\ObligationType;

/**
 * @extends Factory<ObligationType>
 */
class ObligationTypeFactory extends Factory
{
    protected $model = ObligationType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type_name' => fake()->unique()->words(2, true),
            'default_priority' => fake()->randomElement(Priority::values()),
            'default_recurrence_type' => null,
            'default_recurrence_interval' => null,
            'default_risk_level' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'approval_required' => false,
            'renewal_required' => true,
            'active' => true,
        ];
    }
}
