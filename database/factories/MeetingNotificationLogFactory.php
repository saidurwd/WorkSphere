<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingNotificationLog;

/**
 * @extends Factory<MeetingNotificationLog>
 *
 * The module-local notification log Phase 6 consolidated into
 * `notification_logs`. Kept because the dual write still populates it.
 */
class MeetingNotificationLogFactory extends Factory
{
    protected $model = MeetingNotificationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'action_item_id' => null,
            'user_id' => User::factory(),
            'channel' => NotificationChannel::Mail->value,
            'notification_type' => 'meeting.scheduled',
            'scheduled_at' => now(),
            'sent_at' => null,
            'status' => 'pending',
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'retry_count' => 0,
            'error_message' => null,
            'provider_message_id' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
