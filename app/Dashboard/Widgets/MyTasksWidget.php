<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use App\Support\StatusBadge;
use Illuminate\Support\Collection;

/**
 * The viewer's open tasks, nearest deadline first.
 */
class MyTasksWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'my_tasks';
    }

    public function label(): string
    {
        return 'My Tasks';
    }

    public function icon(): string
    {
        return 'list-task';
    }

    public function group(): string
    {
        return 'personal';
    }

    public function permissions(): array
    {
        return ['task.view'];
    }

    public function cacheTtl(): int
    {
        return 60;
    }

    /**
     * Open tasks the viewer owns or is responsible for, capped, ordered by due
     * date with undated work last.
     */
    public function resolve(User $user): Collection
    {
        $rows = $this->visibleTasks($user)
            ->active()
            ->orderByRaw('due_date IS NULL, due_date')
            ->limit(8)
            ->get(['id', 'title', 'due_date', 'status', 'priority']);

        return $rows->map(fn ($task): array => [
            'id' => $task->id,
            'title' => $task->title,
            // `due_date` became nullable in Phase 8; an undated task is a real
            // state and must not render as today's date.
            'due_date' => $task->due_date?->format('M d, Y'),
            'status' => StatusBadge::label($task->status),
            'status_variant' => StatusBadge::variant($task->status),
            'priority' => StatusBadge::label($task->priority),
            'priority_variant' => StatusBadge::priorityVariant($task->priority),
            'url' => route('tasks.show', $task->id),
        ]);
    }
}
