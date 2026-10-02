<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\EscalationRule;
use Modules\Obligations\Models\ObligationType;

/**
 * @extends Factory<EscalationRule>
 *
 * Scoped by `obligation_type_id`, `department_id` and `company_id`, all of which
 * default to NULL. NULL means GLOBAL in this table — that is what every rule
 * created before Phase 8 GAP-031 means — so an unscoped rule is the correct
 * default and the scoped ones are opt-in.
 *
 * `escalation_level` is the string `'1'` because the column is a string, not an
 * integer; a fixture that wrote an int would hide a comparison that stringifies.
 */
class EscalationRuleFactory extends Factory
{
    protected $model = EscalationRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'obligation_type_id' => ObligationType::factory(),
            'department_id' => null,
            'company_id' => null,
            'days_before_expiry' => 30,
            'days_after_expiry' => 7,
            'escalation_level' => '1',
            'recipient_type' => 'owner',
            'channel' => 'mail',
            'active' => true,
        ];
    }

    public function level(string $level): static
    {
        return $this->state(fn (): array => ['escalation_level' => $level]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
