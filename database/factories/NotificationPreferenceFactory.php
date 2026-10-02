<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 *
 * The (user_id, notification_type, channel) triple is unique, and a user may hold
 * several preferences — so the triple is NOT randomised. Every row shares one
 * user and varies only the type and channel, which is exactly the shape a
 * preference screen writes. Randomising the triple would make `create()` fail on
 * the second row for reasons unrelated to what a test is checking.
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_type' => NotificationType::TodoOverdue->value,
            'channel' => NotificationChannel::Mail->value,
            'enabled' => true,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['enabled' => false]);
    }

    public function forType(NotificationType|string $type): static
    {
        return $this->state(fn (): array => [
            'notification_type' => $type instanceof NotificationType ? $type->value : $type,
        ]);
    }

    public function onChannel(NotificationChannel|string $channel): static
    {
        return $this->state(fn (): array => [
            'channel' => $channel instanceof NotificationChannel ? $channel->value : $channel,
        ]);
    }
}
