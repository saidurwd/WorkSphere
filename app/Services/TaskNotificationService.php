<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskNotificationLog;
use App\Models\User;

class TaskNotificationService
{
    public function shouldNotify(User $user, string $type): bool
    {
        return true;
    }

    public function getRecipients(Task $task, string $event): array
    {
        return match ($event) {
            'task_assigned', 'task_updated', 'task_reminder', 'task_overdue' => [$task->responsible_user_id],
            'task_completed' => [$task->user_id],
            default => [],
        };
    }

    public function log(
        ?Task $task,
        ?User $user,
        string $channel,
        string $type,
        string $status,
        ?string $subject = null,
        ?string $message = null,
        ?string $error = null,
    ): TaskNotificationLog {
        return TaskNotificationLog::create([
            'task_id' => $task?->id,
            'user_id' => $user?->id,
            'channel' => $channel,
            'notification_type' => $type,
            'scheduled_at' => now(),
            'status' => $status,
            'subject' => $subject,
            'message' => $message,
            'error_message' => $error,
        ]);
    }
}
