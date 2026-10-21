<?php

namespace Modules\Tasks\Services;

use App\Enums\WorkItemStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Models\Task;

/**
 * The numbers behind the task overview.
 *
 * Everything is computed over ONE base query: all tasks for a user with
 * `task.view`, otherwise only the tasks they created or are responsible for. That
 * includes the per-project breakdown, which the Blade version ran over every task
 * and so showed project names and counts the viewer had no access to.
 */
class TaskDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $base = fn (): Builder => $this->scoped($user);

        $total = $base()->count();
        $completed = $base()->where('status', 'completed')->count();

        $today = now()->startOfDay();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $total - $completed,
            'lists' => [
                'Due today' => $base()->whereDate('due_date', $today)->orderBy('due_date')->limit(5)->get(),
                'Overdue' => $base()->where('status', '!=', 'completed')->where('due_date', '<', $today)->orderBy('due_date')->limit(5)->get(),
                'Upcoming this week' => $base()->where('status', '!=', 'completed')
                    ->whereBetween('due_date', [$today->copy()->addDay(), $today->copy()->endOfWeek()])
                    ->orderBy('due_date')->limit(5)->get(),
                'High priority' => $base()->where('priority', 'high')->where('status', '!=', 'completed')->orderBy('due_date')->limit(5)->get(),
                'Recently completed' => $base()->where('status', 'completed')->latest('completed_at')->limit(5)->get(),
            ],
            'statusBars' => $this->statusBars($base()),
            'priorityBars' => $this->priorityBars($base()),
            'weeklyBars' => $this->weeklyBars($base(), $today),
            'projectBars' => $this->projectBars($base()),
        ];
    }

    /**
     * @return Builder<Task>
     */
    protected function scoped(User $user): Builder
    {
        $query = Task::query();

        if (! $user->hasPermission('task.view')) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->where('tasks.user_id', $user->id)
                    ->orWhere('tasks.responsible_user_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * @return list<array{label: string, value: int, pct: int, key: string}>
     */
    protected function statusBars(Builder $query): array
    {
        $counts = $query->select('status', DB::raw('COUNT(*) as aggregate'))->groupBy('status')->pluck('aggregate', 'status');
        $total = max((int) $counts->sum(), 1);

        return array_map(fn (WorkItemStatus $status): array => [
            'key' => $status->value,
            'label' => $status->label(),
            'value' => (int) $counts->get($status->value, 0),
            'pct' => (int) round((int) $counts->get($status->value, 0) / $total * 100),
        ], WorkItemStatus::TASK_CASES);
    }

    /**
     * @return list<array{label: string, value: int, pct: int, key: string}>
     */
    protected function priorityBars(Builder $query): array
    {
        $counts = $query->select('priority', DB::raw('COUNT(*) as aggregate'))->groupBy('priority')->pluck('aggregate', 'priority');
        $total = max((int) $counts->only(['high', 'medium', 'low'])->sum(), 1);

        return array_map(fn (string $priority): array => [
            'key' => $priority,
            'label' => ucfirst($priority),
            'value' => (int) $counts->get($priority, 0),
            'pct' => (int) round((int) $counts->get($priority, 0) / $total * 100),
        ], ['high', 'medium', 'low']);
    }

    /**
     * Tasks created on each day of the current week.
     *
     * @return list<array{label: string, value: int, pct: int}>
     */
    protected function weeklyBars(Builder $query, Carbon $today): array
    {
        $start = $today->copy()->startOfWeek();

        $perDay = $query
            ->whereBetween('created_at', [$start, $start->copy()->endOfWeek()])
            ->get(['created_at'])
            ->countBy(fn (Task $task): string => $task->created_at->toDateString());

        $max = max((int) $perDay->max(), 1);

        return collect(range(0, 6))->map(function (int $offset) use ($start, $perDay, $max): array {
            $date = $start->copy()->addDays($offset);
            $count = (int) $perDay->get($date->toDateString(), 0);

            return ['label' => $date->format('D'), 'value' => $count, 'pct' => (int) round($count / $max * 100)];
        })->all();
    }

    /**
     * @return list<array{label: string, value: int, pct: int}>
     */
    protected function projectBars(Builder $query): array
    {
        $rows = $query
            ->join('task_projects', 'tasks.project_id', '=', 'task_projects.id')
            ->selectRaw('task_projects.name as name, count(*) as total')
            ->groupBy('task_projects.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $max = max((int) $rows->max('total'), 1);

        return $rows->map(fn ($row): array => [
            'label' => (string) $row->name,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $max * 100),
        ])->all();
    }
}
