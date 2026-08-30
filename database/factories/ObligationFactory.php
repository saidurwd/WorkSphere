<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\ObligationCategory;
use App\Models\ObligationType;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ObligationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'obligation_no' => 'OBS-'.Str::random(8),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'obligation_type_id' => ObligationType::factory(),
            'category_id' => ObligationCategory::factory(),
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'location_id' => Location::factory(),
            'vendor_id' => Vendor::factory(),
            'owner_user_id' => User::factory(),
            'backup_user_id' => null,
            'reviewer_user_id' => null,
            'approver_user_id' => null,
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => fake()->dateTimeBetween('now', '+2 years'),
            'renewal_required' => true,
            'auto_renew' => false,
            'recurrence_type' => null,
            'recurrence_interval' => null,
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'estimated_cost' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'BDT',
            'status' => 'active',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
