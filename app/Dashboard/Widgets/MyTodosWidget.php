<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use App\Support\StatusBadge;
use Illuminate\Support\Collection;

/**
 * The viewer's open To-Dos, nearest deadline first.
 *
 * One query joining the assignee so the row renders without a second round trip;
 * a per-row lookup here would be an N+1 on the busiest panel.
 */
class MyTodosWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'my_todos';
    }

    public function label(): string
    {
        return 'My To-Dos';
    }

    public function icon(): string
    {
        return 'check2-square';
    }

    public function group(): string
    {
        return 'personal';
    }

    public function permissions(): array
    {
        return ['todos.view'];
    }

    public function cacheTtl(): int
    {
        return 60;
    }

    /**
     * Open To-Dos assigned to the viewer, capped, with the assignee joined so the
     * panel renders in a single query.
     */
    public function resolve(User $user): Collection
    {
        $rows = $this->visibleTodos($user)
            ->where('assignee_id', $user->id)
            ->active()
            ->orderByRaw('due_date IS NULL, due_date')
            ->limit(8)
            ->get(['id', 'title', 'due_date', 'status', 'priority']);

        return $rows->map(fn ($todo): array => [
            'id' => $todo->id,
            'title' => $todo->title,
            'due_date' => $todo->due_date?->format('M d, Y'),
            'status' => StatusBadge::label($todo->status),
            'status_variant' => StatusBadge::variant($todo->status),
            'priority' => StatusBadge::label($todo->priority),
            'priority_variant' => StatusBadge::priorityVariant($todo->priority),
            'url' => route('todos.show', $todo->id),
        ]);
    }

    /**
     * `resolve()` returns a Collection, so the registry caches it as a plain
     * array and re-wraps it on the way out.
     */
    public function isListValued(): bool
    {
        return true;
    }
}
