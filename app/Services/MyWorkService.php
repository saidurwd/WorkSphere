<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Todos\Models\Todo;

/**
 * The unified "My Work" feed — TODO-MODULE-SPECIFICATION §10, GAP-034.
 *
 * Assembles Tasks, To-Dos, meeting action items and Obligations into one list.
 *
 * The critical property is that this page applies the **same** filter as each
 * module's own list. A user must not see a row here that they could not open
 * there — an aggregated view that is laxer than the modules it aggregates is a
 * way to read records the module list deliberately hides. `MyWorkService`
 * therefore asks each module's policy rather than re-deriving a shared rule.
 *
 * Permission-gated per source, not once for the page: a user without
 * `obligation.view` must not receive obligation rows *or their count*.
 */
class MyWorkService
{
    public function __construct(private readonly WorkItemQuery $workItems) {}

    /**
     * The sources this user may see, keyed by source type.
     *
     * @return array{sources: list<string>, obligations: bool}
     */
    public function permittedSources(User $user): array
    {
        $sources = [];

        if ($user->hasPermission('todos.view') || $user->hasPermission('todos.view_all')) {
            $sources[] = 'todo';
        }

        if ($user->hasPermission('task.view')) {
            $sources[] = 'task';
        }

        if ($user->hasPermission('meeting.view')) {
            $sources[] = 'meeting_action_item';
        }

        return [
            'sources' => $sources,
            // Obligations are not part of the `work_items` view, so they are
            // gathered separately and gated on their own permission.
            'obligations' => $user->hasPermission('obligation.view'),
        ];
    }

    /**
     * The unified feed, newest deadline first.
     *
     * @return array{workItems: Collection<int, object>, obligations: Collection<int, Obligation>, counts: array<string, int>}
     */
    public function forUser(User $user, int $limit = 50): array
    {
        $permitted = $this->permittedSources($user);

        $departmentId = $user->employee?->department_id === null
            ? null
            : (int) $user->employee->department_id;

        $workItems = $permitted['sources'] === []
            ? collect()
            : $this->workItems
                ->visibleTo($user->id, $permitted['sources'], $departmentId)
                ->limit($limit)
                ->get();

        $workItems = $this->sortByDeadline($workItems);

        $obligations = $permitted['obligations']
            ? $this->obligationsFor($user, $limit)
            : collect();

        return [
            'workItems' => $workItems,
            'obligations' => $obligations,
            'counts' => [
                'tasks' => $this->countFor($workItems, 'task'),
                'todos' => $this->countFor($workItems, 'todo'),
                'action_items' => $this->countFor($workItems, 'meeting_action_item'),
                'obligations' => $obligations->count(),
            ],
        ];
    }

    /**
     * Nearest deadline first, undated work last.
     *
     * Sorted in PHP rather than SQL: `due_date IS NULL` is an expression, and
     * SQLite cannot resolve an expression in an ORDER BY over a wrapped UNION.
     * Sorting the bounded page keeps the view path and the UNION fallback
     * identical, without adding a column to the view purely to order by it.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    protected function sortByDeadline(Collection $rows): Collection
    {
        return $rows->sortBy([
            fn (object $a, object $b): int => ((int) ($a->due_date === null) <=> (int) ($b->due_date === null)) ?: 0,
            fn (object $a, object $b): int => ($a->due_date <=> $b->due_date),
        ])->values();
    }

    /**
     * Obligations the user owns or holds an active responsibility for — the
     * same rule ObligationController applies to its own list.
     *
     * @return Collection<int, Obligation>
     */
    public function obligationsFor(User $user, int $limit): Collection
    {
        return Obligation::query()
            ->where(function ($query) use ($user): void {
                $query->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', function ($inner) use ($user): void {
                        $inner->where('user_id', $user->id)->where('active', true);
                    });
            })
            ->orderByRaw('expiry_date IS NULL, expiry_date')
            ->limit($limit)
            ->get();
    }

    /**
     * The route a work-item row links to, or null when the module is not
     * routable on this install.
     */
    public function urlFor(object $row): ?string
    {
        return match ($row->source_type) {
            'todo' => route('todos.show', $row->source_id),
            'task' => route('tasks.show', $row->source_id),
            'meeting_action_item' => route('meetings.show', MeetingActionItem::query()->find($row->source_id)?->meeting_id),
            default => null,
        };
    }

    /**
     * Resolve a row to its model for the detail view.
     */
    public function modelFor(object $row): ?Model
    {
        return $this->workItems->resolve($row->source_type, (int) $row->source_id);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    protected function countFor(Collection $rows, string $sourceType): int
    {
        return $rows->filter(fn (object $row): bool => $row->source_type === $sourceType)->count();
    }
}
