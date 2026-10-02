<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskNotificationLog;

/**
 * @extends Factory<TaskNotificationLog>
 *
 * The module-local notification log that Phase 6 consolidated into
 * `notification_logs`. Dual-written during the migration window, so the factory
 * fills the shape the module service still writes.
 */
class TaskNotificationLogFactory extends Factory
{
    protected $model = TaskNotificationLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'channel' => NotificationChannel::Mail->value,
            'notification_type' => 'task.assigned',
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

    public function failed(string $reason = 'SMTP unavailable'): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'error_message' => $reason,
            'retry_count' => 1,
        ]);
    }
}
