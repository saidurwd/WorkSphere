<?php

namespace Database\Factories;

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
            // `obligations.priority` is NOT the shared `Priority` enum. Every write path
            // in the application validates it as `low|medium|high|critical`
            // (ObligationController store and update), and the seeder uses only
            // those four. The shared enum additionally carries `normal`,
            // `important` and `urgent`, which belong to `meetings.priority` and
            // `meeting_action_items.priority`.
            //
            // A factory that draws from the wrong vocabulary builds rows the
            // application would refuse to create — and, because the dashboard
            // maps priority to a badge with an EXHAUSTIVE match, those rows 500'd
            // a real page. The vocabulary is the one the application enforces.
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
