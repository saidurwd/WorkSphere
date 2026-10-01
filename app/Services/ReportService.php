<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Models\Task;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The shared report layer — GAP-036.
 *
 * Meetings and Obligations already had report services; Tasks had NONE, which is
 * the gap this closes. Rather than three similar services, the shared query and
 * export machinery lives here and each module supplies its own visibility rule.
 *
 * The invariant every method keeps:
 *
 * - aggregation happens in SQL. Nothing here loads rows to count them.
 * - the rows a report returns are the rows its export returns. An export that is
 *   broader than the screen it was launched from is a privilege-escalation path.
 * - permissions are applied by the caller's `applyVisibility()`, never assumed.
 */
class ReportService
{
    public function __construct(private readonly CsvExporter $csv) {}

    /**
     * Task completion over a window, grouped by assignee.
     *
     * @return Collection<int, object>
     */
    public function taskCompletionByOwner(string $from, string $to, ?User $viewer = null): Collection
    {
        $query = $this->taskQuery($viewer)
            ->join('users', 'users.id', '=', 'tasks.responsible_user_id')
            ->whereDate('tasks.created_at', '>=', $from)
            ->whereDate('tasks.created_at', '<=', $to)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'));

        // NOTE: the conditional aggregates use selectRaw() rather than a
        // DB::raw inside the get() column list. A DB::raw with bindings placed
        // there registers them ahead of the where-bindings, so the date filter
        // received the literal 'completed' and the report silently returned
        // nothing. selectRaw appends in the correct order.

        return $query
            ->selectRaw('COUNT(*) AS created_total')
            ->selectRaw('SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS completed_total', ['completed'])
            ->selectRaw('SUM(CASE WHEN tasks.status <> ? AND tasks.due_date IS NOT NULL AND tasks.due_date < ? THEN 1 ELSE 0 END) AS overdue_total', [
                'completed', now()->toDateString(),
            ])
            ->addSelect(['users.id as assignee_id', 'users.name as assignee'])
            ->get();
    }

    /**
     * Tasks grouped by project.
     *
     * @return Collection<int, object>
     */
    public function taskCompletionByProject(string $from, string $to, ?User $viewer = null): Collection
    {
        return $this->taskQuery($viewer)
            ->join('task_projects', 'task_projects.id', '=', 'tasks.project_id')
            ->whereDate('tasks.created_at', '>=', $from)
            ->whereDate('tasks.created_at', '<=', $to)
            ->groupBy('task_projects.id', 'task_projects.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS completed', ['completed'])
            ->addSelect(['task_projects.id as project_id', 'task_projects.name as project'])
            ->get();
    }

    /**
     * Tasks per assignee, for the workload report. Reports on other people, so
     * the caller must hold `task.view_all`.
     *
     * @return Collection<int, object>
     */
    public function taskWorkload(?User $viewer = null): Collection
    {
        return $this->taskQuery($viewer)
            ->join('users', 'users.id', '=', 'tasks.responsible_user_id')
            ->whereNotNull('tasks.responsible_user_id')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS completed', ['completed'])
            ->selectRaw('SUM(CASE WHEN tasks.status <> ? AND tasks.due_date IS NOT NULL AND tasks.due_date < ? THEN 1 ELSE 0 END) AS overdue', [
                'completed', now()->toDateString(),
            ])
            ->addSelect(['users.id as assignee_id', 'users.name as assignee'])
            ->get();
    }

    /**
     * Tasks by status and priority, the two dimensions the dashboard charts use.
     *
     * @return Collection<int, object>
     */
    public function taskDistribution(?User $viewer = null): Collection
    {
        return $this->taskQuery($viewer)
            ->groupBy('status', 'priority')
            ->orderBy('status')
            ->orderBy('priority')
            ->selectRaw('COUNT(*) AS total')
            ->addSelect(['status', 'priority'])
            ->get();
    }

    /**
     * CSV export for the completion report.
     *
     * Built from the same call the screen uses, so the file cannot describe a
     * different set than the table above it.
     */
    public function exportTaskCompletionByOwner(string $from, string $to, ?User $viewer = null): StreamedResponse
    {
        $rows = $this->taskCompletionByOwner($from, $to, $viewer)->map(fn (object $row): array => [
            (string) $row->assignee,
            (int) $row->created_total,
            (int) $row->completed_total,
            (int) $row->overdue_total,
            (int) $row->created_total === 0
                ? 0
                : (int) round(((int) $row->completed_total / (int) $row->created_total) * 100),
        ])->all();

        return $this->csv->download(
            'task-completion',
            ['Assignee', 'Created', 'Completed', 'Overdue', 'Completion %'],
            $rows,
            $from,
            $to,
        );
    }

    public function exportTaskWorkload(?User $viewer = null): StreamedResponse
    {
        $rows = $this->taskWorkload($viewer)->map(fn (object $row): array => [
            (string) $row->assignee,
            (int) $row->total,
            (int) $row->completed,
            (int) $row->overdue,
        ])->all();

        return $this->csv->download(
            'task-workload',
            ['Assignee', 'Total', 'Completed', 'Overdue'],
            $rows,
        );
    }

    public function exportTaskDistribution(?User $viewer = null): StreamedResponse
    {
        $rows = $this->taskDistribution($viewer)->map(fn (object $row): array => [
            (string) $row->status,
            (string) $row->priority,
            (int) $row->total,
        ])->all();

        return $this->csv->download('task-distribution', ['Status', 'Priority', 'Total'], $rows);
    }

    /**
     * The base task query, narrowed to what the viewer may see.
     *
     * A null viewer means an unfiltered report, which only a system caller may
     * ask for — the controller always passes the authenticated user.
     *
     * @return Builder<Task>
     */
    protected function taskQuery(?User $viewer): Builder
    {
        $query = Task::query();

        if ($viewer === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($viewer): void {
            $inner->where('tasks.user_id', $viewer->id)
                ->orWhere('tasks.responsible_user_id', $viewer->id)
                ->orWhereHas('watchers', fn ($watchers): mixed => $watchers->where('user_id', $viewer->id));
        });
    }
}
