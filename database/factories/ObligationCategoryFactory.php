<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\ObligationCategory;

/**
 * @extends Factory<ObligationCategory>
 */
class ObligationCategoryFactory extends Factory
{
    protected $model = ObligationCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_name' => fake()->unique()->words(2, true),
            'active' => true,
        ];
    }
}
