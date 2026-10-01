<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\MeetingType;

/**
 * @extends Factory<MeetingType>
 */
class MeetingTypeFactory extends Factory
{
    protected $model = MeetingType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
