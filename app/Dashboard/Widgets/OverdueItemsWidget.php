<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Everything the viewer is party to that is past its date and still open.
 *
 * Tasks, To-Dos and obligations in one panel because "what have I let slip" is
 * one question, and splitting it across three panels is what made it easy to
 * miss. Three queries, each already narrowed to the viewer.
 */
class OverdueItemsWidget implements DashboardWidget
{
    use Concerns;

    private const LIMIT = 8;

    public function key(): string
    {
        return 'overdue_items';
    }

    public function label(): string
    {
        return 'Overdue';
    }

    public function icon(): string
    {
        return 'exclamation-triangle';
    }

    public function group(): string
    {
        return 'personal';
    }

    /**
     * Requires nothing beyond the three data permissions being *partly* relevant;
     * the widget is always shown and simply reports nothing when the user has no
     * permission on any of the three sources. That is why the check is
     * "all of these OR that one" rather than a single permission.
     */
    public function permissions(): array
    {
        return [];
    }

    public function cacheTtl(): int
    {
        return 60;
    }

    /**
     * Open, past-due rows from each source the viewer may see, merged and ordered
     * by how long they have been late.
     */
    public function resolve(User $user): Collection
    {
        $today = now()->toDateString();
        $canSeeTasks = $user->hasPermission('task.view');
        $canSeeTodos = $user->hasPermission('todos.view');
        $canSeeObligations = $user->hasPermission('obligation.view');

        $items = [];

        if ($canSeeTasks) {
            foreach ($this->visibleTasks($user)->active()
                ->whereNotNull('due_date')->whereDate('due_date', '<', $today)
                ->orderBy('due_date')->limit(self::LIMIT)->get(['id', 'title', 'due_date']) as $row) {
                $items[] = $this->present('Task', $row->title, $row->due_date, route('tasks.show', $row->id));
            }
        }

        if ($canSeeTodos) {
            foreach ($this->visibleTodos($user)->active()
                ->whereNotNull('due_date')->whereDate('due_date', '<', $today)
                ->orderBy('due_date')->limit(self::LIMIT)->get(['id', 'title', 'due_date']) as $row) {
                $items[] = $this->present('To-Do', $row->title, $row->due_date, route('todos.show', $row->id));
            }
        }

        if ($canSeeObligations) {
            foreach ($this->visibleObligations($user)
                ->whereNotNull('expiry_date')->whereDate('expiry_date', '<', $today)
                ->whereNotIn('status', $this->closedObligationStatuses())
                ->orderBy('expiry_date')->limit(self::LIMIT)->get(['id', 'title', 'expiry_date']) as $row) {
                $items[] = $this->present('Obligation', $row->title, $row->expiry_date, route('obligations.show', $row->id));
            }
        }

        // A 200-line sort in PHP would be a smell; the set is already bounded to
        // three capped queries, so ordering what we have is the cheaper choice.
        usort($items, static fn (array $a, array $b): int => $a['overdue_days'] <=> $b['overdue_days']);

        return collect(array_slice($items, 0, self::LIMIT));
    }

    /**
     * `resolve()` returns a Collection, so the registry caches it as a plain
     * array and re-wraps it on the way out.
     */
    public function isListValued(): bool
    {
        return true;
    }

    /**
     * @return array{source: string, title: string, due: string, overdue_days: int, url: string}
     */
    protected function present(string $source, string $title, mixed $dueDate, string $url): array
    {
        $days = $dueDate === null
            ? 0
            : (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false);

        return [
            'source' => $source,
            'title' => $title,
            'due' => $dueDate?->format('M d, Y') ?? '—',
            'overdue_days' => abs($days),
            'url' => $url,
        ];
    }
}
