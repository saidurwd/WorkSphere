<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Everything due in the next seven days, across the modules the viewer can see.
 *
 * One query per source, each already permission-narrowed, merged and capped. The
 * merge is in PHP over three bounded result sets, not over a table.
 */
class UpcomingDeadlinesWidget implements DashboardWidget
{
    use Concerns;

    private const LIMIT = 8;

    public function key(): string
    {
        return 'upcoming_deadlines';
    }

    public function label(): string
    {
        return 'Next 7 Days';
    }

    public function icon(): string
    {
        return 'calendar3';
    }

    public function group(): string
    {
        return 'personal';
    }

    public function permissions(): array
    {
        return [];
    }

    public function cacheTtl(): int
    {
        return 120;
    }

    /**
     * Open rows due between today and the end of the week, from each source the
     * viewer holds a permission for.
     */
    public function resolve(User $user): Collection
    {
        $from = now()->toDateString();
        $to = now()->endOfWeek()->toDateString();

        $items = [];

        if ($user->hasPermission('task.view')) {
            foreach ($this->visibleTasks($user)->active()
                ->dueBetween($from, $to)->orderBy('due_date')->limit(self::LIMIT)->get(['id', 'title', 'due_date']) as $row) {
                $items[] = $this->present('Task', $row->title, $row->due_date, route('tasks.show', $row->id));
            }
        }

        if ($user->hasPermission('todos.view')) {
            foreach ($this->visibleTodos($user)->active()
                ->dueBetween($from, $to)->orderBy('due_date')->limit(self::LIMIT)->get(['id', 'title', 'due_date']) as $row) {
                $items[] = $this->present('To-Do', $row->title, $row->due_date, route('todos.show', $row->id));
            }
        }

        if ($user->hasPermission('obligation.view')) {
            foreach ($this->visibleObligations($user)
                ->whereBetween('expiry_date', [$from, $to])
                ->whereNotIn('status', $this->closedObligationStatuses())
                ->orderBy('expiry_date')->limit(self::LIMIT)->get(['id', 'title', 'expiry_date']) as $row) {
                $items[] = $this->present('Obligation', $row->title, $row->expiry_date, route('obligations.show', $row->id));
            }
        }

        usort($items, static fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return collect(array_slice($items, 0, self::LIMIT));
    }

    /**
     * @return array{source: string, title: string, due: string, days: int, sort: string, url: string}
     */
    protected function present(string $source, string $title, mixed $dueDate, string $url): array
    {
        return [
            'source' => $source,
            'title' => $title,
            'due' => $dueDate?->format('M d, Y') ?? '—',
            'days' => $dueDate === null ? 99 : (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false),
            'sort' => $dueDate?->toDateString() ?? '9999-12-31',
            'url' => $url,
        ];
    }
}
