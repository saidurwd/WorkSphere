<?php

namespace Modules\Meetings\Services;

use App\Enums\WorkItemStatus;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Tasks\Models\Task;

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

        // `Task::$status` is cast to `WorkItemStatus`, so `$task->status` is an enum
        // instance. Matching it against the STRINGS 'completed'/'in_progress'/'pending'
        // never matched, and every arm including the mirror of a completed task fell
        // through to `default => 'open'` — an action item could never be marked
        // completed by its task. Matching the cases fixes that and gives the two new
        // statuses somewhere to go.
        $status = match ($task->status) {
            WorkItemStatus::Completed => 'completed',
            WorkItemStatus::InProgress => 'in_progress',
            WorkItemStatus::Cancelled => 'cancelled',
            // A deferred task is not open work and not finished: the action item
            // keeps the status it had, which is the closest honest answer given
            // `meeting_action_items` has no `postponed`.
            WorkItemStatus::Postponed, WorkItemStatus::OnHold => null,
            default => 'open',
        };

        // Nothing to sync when the task's state has no action-item equivalent.
        if ($status === null) {
            return;
        }

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
