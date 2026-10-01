<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationCategory;
use Modules\Obligations\Models\ObligationType;
use Modules\Obligations\Models\Vendor;

class ObligationFactory extends Factory
{
    protected $model = Obligation::class;

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
            // `critical` is a risk_level value, not a priority one. Leaking it in
            // here produced rows that the shared Priority enum cannot represent —
            // and a factory row only fails when something casts it, so the failure
            // surfaced as an unrelated random failure.
            'priority' => fake()->randomElement(Priority::values()),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'estimated_cost' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'BDT',
            'status' => 'active',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
