<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\Obligation;

/**
 * @extends Factory<NotificationLog>
 *
 * `dedupe_key` carries a UNIQUE index, and that index — not a check-then-insert
 * — is what makes every reminder command safely re-runnable. The factory therefore
 * generates a distinct key per row, so a test that creates two notifications and
 * asserts ONE was delivered is testing dedupe logic rather than colliding with a
 * fixture.
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'obligation_id' => Obligation::factory(),
            'user_id' => User::factory(),
            'notification_rule_id' => null,
            'channel' => NotificationChannel::Mail->value,
            'notification_type' => 'obligation.expiry_reminder',
            'scheduled_at' => now(),
            'sent_at' => null,
            'status' => 'pending',
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'retry_count' => 0,
            'error_message' => null,
            'provider_message_id' => null,
            'dedupe_key' => 'factory:'.Str::lower(Str::random(24)),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(string $reason = 'SMTP unavailable'): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'error_message' => $reason,
            'retry_count' => 1,
        ]);
    }
}
