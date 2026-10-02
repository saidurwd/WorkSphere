<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todos\Models\Todo;

/**
 * @extends Factory<Reminder>
 *
 * (subject_type, subject_id, remind_at) is unique, and that index — not any code
 * path — is what makes a re-run of the reminder dispatcher safe. `remind_at`
 * therefore varies by a random number of SECONDS: a fixed future timestamp would
 * make the second `Reminder::factory()` collide and fail the idempotency tests for
 * a reason that has nothing to do with dedupe.
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => (new Todo)->getMorphClass(),
            'subject_id' => Todo::factory(),
            'remind_at' => now()->addSeconds(fake()->numberBetween(60, 86_400)),
            'channel' => NotificationChannel::Mail->value,
            'status' => ReminderStatus::Pending->value,
            'sent_at' => null,
            'cancelled_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * A reminder that is due, which is the only state the dispatcher selects on.
     */
    public function due(): static
    {
        return $this->state(fn (): array => [
            'remind_at' => now()->subMinutes(fake()->numberBetween(1, 120)),
            'status' => ReminderStatus::Pending->value,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'remind_at' => now()->subDay(),
            'status' => ReminderStatus::Sent->value,
            'sent_at' => now()->subHour(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => ReminderStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }
}
