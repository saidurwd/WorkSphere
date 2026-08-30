<?php

namespace App\Services;

use App\Models\MeetingActionItem;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class MeetingActionTaskService
{
    public function createTask(MeetingActionItem $actionItem, array $taskData = []): ?Task
    {
        if ($actionItem->task_id) {
            return null;
        }

        $task = DB::transaction(function () use ($actionItem, $taskData) {
            $task = new Task(array_merge([
                'title' => $actionItem->title,
                'description' => $actionItem->description,
                'priority' => $this->mapPriority($actionItem->priority),
                'status' => 'pending',
                'due_date' => $actionItem->due_date,
                'user_id' => $actionItem->assigned_to,
                'responsible_user_id' => $actionItem->assigned_to,
            ], $taskData));

            $task->save();

            $actionItem->update(['task_id' => $task->id]);

            return $task;
        });

        return $task;
    }

    public function linkTask(MeetingActionItem $actionItem, Task $task): MeetingActionItem
    {
        if ($actionItem->task_id && $actionItem->task_id !== $task->id) {
            throw new \InvalidArgumentException('Action item is already linked to a different task.');
        }

        $actionItem->update(['task_id' => $task->id]);

        return $actionItem;
    }

    public function syncStatus(MeetingActionItem $actionItem): void
    {
        if (! $actionItem->task_id) {
            return;
        }

        $task = Task::find($actionItem->task_id);
        if (! $task) {
            return;
        }

        $status = match ($task->status) {
            'completed' => 'completed',
            'in_progress' => 'in_progress',
            'pending' => 'open',
            default => 'open',
        };

        $updates = ['status' => $status];

        if ($task->completed_at) {
            $updates['completed_at'] = $task->completed_at;
            $updates['completed_by'] = $task->responsible_user_id ?? $task->user_id;
        }

        $actionItem->update($updates);
    }

    private function mapPriority(string $meetingPriority): string
    {
        return match ($meetingPriority) {
            'urgent' => 'high',
            'high' => 'high',
            'normal' => 'medium',
            'low' => 'low',
            default => 'medium',
        };
    }
}
