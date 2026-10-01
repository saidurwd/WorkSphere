<?php

namespace Modules\Todos\Services;

use App\Enums\WorkItemStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Models\Todo;

/**
 * To-Do reporting — TODO-MODULE-SPECIFICATION.md §10.
 *
 * Every figure here is produced by SQL aggregation. Nothing loads rows into PHP
 * to be counted: at 50k To-Dos that is the difference between a report and a
 * table scan on the dashboard. This mirrors MeetingReportService, which is the
 * correct existing pattern in this codebase.
 *
 * `DATE_FORMAT` is MySQL-specific, so the date-bucketing queries take a driver
 * branch. SQLite has no equivalent; `strftime` produces the same bucket, which
 * keeps the test suite exercising the real code path rather than a stub.
 */
class TodoReportService
{
    /**
     * Completed To-Dos per period, grouped by owner. §10 Completion.
     *
     * @return Collection<int, object>
     */
    public function completionByOwner(string $from, string $to): Collection
    {
        return DB::table('todos')
            ->selectRaw('assignee_id, COUNT(*) AS completed_total')
            ->where('status', WorkItemStatus::Completed->value)
            ->whereNull('deleted_at')
            ->whereNotNull('assignee_id')
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('assignee_id')
            ->orderByDesc('completed_total')
            ->get();
    }

    /**
     * Completed To-Dos per period, grouped by department. §10 Completion.
     *
     * @return Collection<int, object>
     */
    public function completionByDepartment(string $from, string $to): Collection
    {
        return DB::table('todos')
            ->selectRaw('department_id, COUNT(*) AS completed_total')
            ->where('status', WorkItemStatus::Completed->value)
            ->whereNull('deleted_at')
            ->whereNotNull('department_id')
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('department_id')
            ->orderByDesc('completed_total')
            ->get();
    }

    /**
     * `due_date < today AND status NOT IN (Completed, Archived)`. §10 Overdue.
     *
     * @return Collection<int, object>
     */
    public function overdueByOwner(?string $asOf = null): Collection
    {
        return DB::table('todos')
            ->selectRaw('assignee_id, COUNT(*) AS overdue_total')
            ->whereIn('status', WorkItemStatus::openValues())
            ->whereNull('deleted_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $asOf ?? now()->toDateString())
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->orderByDesc('overdue_total')
            ->get();
    }

    /**
     * Per-user created vs completed, with a completion rate. §10 Personal
     * productivity.
     *
     * @return Collection<int, object>
     */
    public function personalProductivity(string $from, string $to): Collection
    {
        $driver = DB::connection()->getDriverName();

        $averageCompletion = match ($driver) {
            'sqlite' => 'AVG(CASE WHEN status = ? THEN CAST(julianday(completed_at) - julianday(start_date) AS INTEGER) END)',
            default => 'AVG(CASE WHEN status = ? THEN DATEDIFF(completed_at, start_date) END)',
        };

        return DB::table('todos')
            ->selectRaw('creator_id, COUNT(*) AS created_total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed_total', [
                WorkItemStatus::Completed->value,
            ])
            ->selectRaw("{$averageCompletion} AS avg_days_to_complete", [WorkItemStatus::Completed->value])
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('creator_id')
            ->orderByDesc('created_total')
            ->get()
            ->map(function (object $row): object {
                $created = (int) $row->created_total;

                $row->completion_rate = $created === 0
                    ? 0
                    : (int) round(((int) $row->completed_total / $created) * 100);

                return $row;
            });
    }

    /**
     * Workload distribution per assignee: how much open work each person is
     * carrying. §10 Team productivity.
     *
     * @return Collection<int, object>
     */
    public function workloadDistribution(?int $departmentId = null): Collection
    {
        $query = DB::table('todos')
            ->selectRaw('assignee_id, COUNT(*) AS open_total')
            ->selectRaw('SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) AS overdue_total', [
                now()->toDateString(),
            ])
            ->whereNull('deleted_at')
            ->whereIn('status', WorkItemStatus::openValues())
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->orderByDesc('open_total');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        return $query->get();
    }

    /**
     * Overdue count bucketed by week across a range. §10 Overdue trend.
     *
     * Week buckets are computed in SQL so the series is one query; doing it in
     * PHP would require loading every overdue row and grouping in memory.
     *
     * @return Collection<int, object>
     */
    public function overdueTrend(string $from, string $to): Collection
    {
        $driver = DB::connection()->getDriverName();

        $bucket = match ($driver) {
            'sqlite' => "strftime('%Y-W%W', due_date)",
            default => "DATE_FORMAT(due_date, '%x-W%v')",
        };

        return DB::table('todos')
            ->selectRaw("{$bucket} AS period, COUNT(*) AS overdue_total")
            ->whereNull('deleted_at')
            ->whereIn('status', WorkItemStatus::openValues())
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $from)
            ->whereDate('due_date', '<=', $to)
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * For recurring To-Dos: how many were generated versus completed.
     * §10 Recurrence adherence.
     *
     * @return Collection<int, object>
     */
    public function recurrenceAdherence(string $from, string $to): Collection
    {
        return DB::table('todos')
            ->selectRaw('creator_id, COUNT(*) AS generated_total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed_total', [
                WorkItemStatus::Completed->value,
            ])
            ->whereNull('deleted_at')
            ->whereNotNull('recurrence_rule')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('creator_id')
            ->orderByDesc('generated_total')
            ->get()
            ->map(function (object $row): object {
                $generated = (int) $row->generated_total;

                $row->adherence_rate = $generated === 0
                    ? 0
                    : (int) round(((int) $row->completed_total / $generated) * 100);

                return $row;
            });
    }

    /**
     * Headline counters for the To-Do overview card. One query, one row — the
     * dashboard must not make six round trips to draw one widget.
     *
     * @return object{open: int, overdue: int, completed: int, unassigned: int}
     */
    public function summary(): object
    {
        $today = now()->toDateString();

        return DB::table('todos')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?, ?, ?, ?, ?, ?, ?) THEN 1 ELSE 0 END) AS open', WorkItemStatus::openValues())
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?, ?, ?, ?, ?, ?, ?) AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) AS overdue', [
                ...WorkItemStatus::openValues(),
                $today,
            ])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed', [WorkItemStatus::Completed->value])
            ->selectRaw('SUM(CASE WHEN assignee_id IS NULL THEN 1 ELSE 0 END) AS unassigned')
            ->whereNull('deleted_at')
            ->first();
    }
}
