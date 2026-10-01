<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\Vendor;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_name' => fake()->unique()->company(),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->optional()->numerify('+8801#########'),
            'website' => fake()->optional()->url(),
            'status' => 'active',
        ];
    }
}
