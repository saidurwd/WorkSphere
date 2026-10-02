<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Open work per assignee, so a manager can see who is carrying what.
 *
 * Management, not personal: it reports on OTHER people, so it is gated behind a
 * management permission and never falls back to "is the user a manager" by role
 * slug.
 */
class TeamWorkloadWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'team_workload';
    }

    public function label(): string
    {
        return 'Team Workload';
    }

    public function icon(): string
    {
        return 'people';
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
        return 180;
    }

    /**
     * Open tasks grouped by responsible user, with the overdue count as a
     * conditional aggregate, joined to `users` so the name comes back in the same
     * query rather than one lookup per row.
     */
    public function resolve(User $user): Collection
    {
        $today = now()->toDateString();

        return $this->visibleTasks($user)
            ->join('users', 'users.id', '=', 'tasks.responsible_user_id')
            ->active()
            ->groupBy('users.id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->selectRaw('COUNT(*) AS open_total')
            ->selectRaw('SUM(CASE WHEN tasks.due_date IS NOT NULL AND tasks.due_date < ? THEN 1 ELSE 0 END) AS overdue_total', [$today])
            ->get(['users.id as assignee_id', 'users.name as assignee'])
            ->map(fn ($row): array => [
                'assignee' => $row->assignee,
                'open' => (int) $row->open_total,
                'overdue' => (int) $row->overdue_total,
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
