<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use App\Support\StatusBadge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Task counts by status, for a whole team.
 */
class TaskDistributionWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'task_distribution';
    }

    public function label(): string
    {
        return 'Task Distribution';
    }

    public function icon(): string
    {
        return 'pie-chart';
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
     * One grouped count per status across the visible tasks, so the whole panel is
     * a single pass over the table.
     */
    public function resolve(User $user): Collection
    {
        $rows = $this->visibleTasks($user)
            ->groupBy('status')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('COUNT(*) AS total')
            ->addSelect(['status'])
            ->get();

        $total = (int) $rows->sum('total');

        return $rows->map(fn ($row): array => [
            'status' => $row->status,
            'label' => StatusBadge::label($row->status),
            'variant' => StatusBadge::variant($row->status),
            // `label`, `value` and `color` are the keys x-donut-chart and x-legend
            // read. `count` is kept for the totals, `pct` for the table.
            'color' => self::colorFor(StatusBadge::variant($row->status)),
            'value' => (int) $row->total,
            'count' => (int) $row->total,
            'pct' => $total === 0 ? 0 : (int) round(((int) $row->total / $total) * 100),
        ]);
    }

    /**
     * A Bootstrap contextual variant mapped to the CSS custom properties the
     * chart components already use, so a status keeps one colour everywhere.
     */
    public static function colorFor(string $variant): string
    {
        return match ($variant) {
            'success' => 'var(--bs-success)',
            'danger' => 'var(--bs-danger)',
            'warning' => 'var(--bs-warning)',
            'info' => 'var(--bs-info)',
            'primary' => 'var(--bs-primary)',
            default => 'var(--bs-secondary)',
        };
    }
}
