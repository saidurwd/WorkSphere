<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Share of tasks completed in the last 30 days, per assignee.
 */
class CompletionRateWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'completion_rate';
    }

    public function label(): string
    {
        return 'Completion Rate';
    }

    public function icon(): string
    {
        return 'graph-up-arrow';
    }

    public function group(): string
    {
        return 'management';
    }

    public function permissions(): array
    {
        return ['task.view_all', 'report.view'];
    }

    public function cacheTtl(): int
    {
        return 300;
    }

    /**
     * Tasks created and completed in the last 30 days grouped by assignee, with
     * the rate computed in SQL so the PHP side never sums a result set.
     */
    public function resolve(User $user): Collection
    {
        $from = now()->subDays(30)->toDateString();

        return $this->visibleTasks($user)
            ->join('users', 'users.id', '=', 'tasks.responsible_user_id')
            ->whereDate('tasks.created_at', '>=', $from)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->get([
                'users.name as assignee',
                DB::raw('COUNT(*) AS created_total'),
                DB::raw('SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS completed_total', ['completed']),
            ])
            ->map(fn ($row): array => [
                'assignee' => $row->assignee,
                'created' => (int) $row->created_total,
                'completed' => (int) $row->completed_total,
                'rate' => (int) $row->created_total === 0
                    ? 0
                    : (int) round(((int) $row->completed_total / (int) $row->created_total) * 100),
            ]);
    }
}
