<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_name' => fake()->unique()->city().' Office',
            'location_code' => strtoupper(fake()->unique()->bothify('LOC-####')),
            'city' => fake()->city(),
            'country' => 'Bangladesh',
            'status' => 'active',
        ];
    }
}
