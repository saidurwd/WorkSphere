<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationResponsibility;

/**
 * @extends Factory<ObligationResponsibility>
 *
 * (obligation_id, user_id, responsibility_type) is unique. `active` defaults to
 * TRUE because a responsibility that has been given but not yet discharged is the
 * common case — and because `ObligationPolicy` and the API list filter both check
 * `active`, a factory defaulting to false would quietly stop granting the
 * visibility it is supposed to model.
 */
class ObligationResponsibilityFactory extends Factory
{
    protected $model = ObligationResponsibility::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'obligation_id' => Obligation::factory(),
            'user_id' => User::factory(),
            'responsibility_type' => 'responsible',
            'escalation_level' => 1,
            'active' => true,
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn (): array => ['responsibility_type' => $type]);
    }

    /**
     * A discharged responsibility. This one MUST stop granting visibility — the
     * policy and the list filter both key on `active`, and a closed-out row that
     * kept its access is the bug this state exists to catch.
     */
    public function discharged(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
