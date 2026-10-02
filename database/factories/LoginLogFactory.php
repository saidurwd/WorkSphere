<?php

namespace Database\Factories;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginLog>
 *
 * `email` is the only required column, and for a FAILED login there is no user at
 * all — which is why it is NOT NULL while `user_id` is nullable. The default
 * therefore produces a failure row: the case that used to fatal on a missing
 * import before Phase 2.
 */
class LoginLogFactory extends Factory
{
    protected $model = LoginLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'event' => LoginLog::FAILED,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Factory',
            'device' => 'unknown',
            'failure_reason' => 'Invalid credentials.',
            'attempted_at' => now(),
        ];
    }

    public function succeeded(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'email' => $user->email,
            'event' => LoginLog::LOGIN,
            'failure_reason' => null,
            'attempted_at' => now(),
        ]);
    }
}
