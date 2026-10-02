<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Task completion by owning department.
 */
class DepartmentPerformanceWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'department_performance';
    }

    public function label(): string
    {
        return 'Department Performance';
    }

    public function icon(): string
    {
        return 'diagram-3';
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
     * Tasks joined through their owner to their owner's department, grouped, with
     * created and completed counted in the same pass. Departments with no tasks
     * are excluded rather than shown as a misleading zero.
     */
    public function resolve(User $user): Collection
    {
        // A task has no `department_id`. Its owner's department is two hops away —
        // task -> user -> employee -> department — so the join has to go through
        // both, and tasks with no linked employee are excluded rather than
        // appearing as a misleading "no department" row.
        return $this->visibleTasks($user)
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->join('employees', 'employees.id', '=', 'users.employee_id')
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->groupBy('departments.id', 'departments.department_name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS completed', ['completed'])
            ->get(['departments.department_name'])
            ->map(fn ($row): array => [
                'department' => $row->department_name,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
                'rate' => (int) $row->total === 0
                    ? 0
                    : (int) round(((int) $row->completed / (int) $row->total) * 100),
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
