<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationActivityLog;

/**
 * @extends Factory<ObligationActivityLog>
 *
 * The module's own activity trail, superseded by the shared `activity_logs` in
 * Phase 8. Kept because the dual write still populates it, and because a factory
 * for the legacy path is what proves the dual write is not dead.
 */
class ObligationActivityLogFactory extends Factory
{
    protected $model = ObligationActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'obligation_id' => Obligation::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'approved', 'renewed']),
            'old_value' => null,
            'new_value' => null,
            'remarks' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Factory',
        ];
    }
}
