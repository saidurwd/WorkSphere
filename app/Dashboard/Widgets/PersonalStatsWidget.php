<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The headline counters at the top of the dashboard.
 *
 * ONE query per source table, each a conditional aggregate, rather than one
 * query per counter: the previous implementation issued six.
 */
class PersonalStatsWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'personal_stats';
    }

    public function label(): string
    {
        return 'Overview';
    }

    public function icon(): string
    {
        return 'speedometer2';
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
        return 60;
    }

    /**
     * Three conditional aggregates, one per source, each counting open work,
     * completed work and anything past its date in a single pass over the table.
     */
    public function resolve(User $user): array
    {
        $today = now()->toDateString();

        $tasks = $this->visibleTasks($user)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed', ['completed'])
            ->selectRaw('SUM(CASE WHEN status <> ? AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) AS overdue', ['completed', $today])
            ->first();

        $todos = $this->visibleTodos($user)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed', ['completed'])
            ->selectRaw('SUM(CASE WHEN status NOT IN (?, ?) AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) AS overdue', ['completed', 'archived', $today])
            ->first();

        $meetings = $this->visibleMeetings($user)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed', ['completed'])
            ->selectRaw('SUM(CASE WHEN status = ? AND meeting_date >= ? THEN 1 ELSE 0 END) AS upcoming', ['scheduled', $today])
            ->first();

        return [
            // Feeds the "tasks created this week" bar chart. One grouped query
            // bucketed by day, rather than seven separate counts.
            'weeklyBars' => $this->weeklyBars($this->visibleTasks($user)),
            'tasks' => [
                'total' => (int) ($tasks->total ?? 0),
                'completed' => (int) ($tasks->completed ?? 0),
                'open' => (int) ($tasks->total ?? 0) - (int) ($tasks->completed ?? 0),
                'overdue' => (int) ($tasks->overdue ?? 0),
            ],
            'todos' => [
                'total' => (int) ($todos->total ?? 0),
                'completed' => (int) ($todos->completed ?? 0),
                'open' => (int) ($todos->total ?? 0) - (int) ($todos->completed ?? 0),
                'overdue' => (int) ($todos->overdue ?? 0),
            ],
            'meetings' => [
                'total' => (int) ($meetings->total ?? 0),
                'completed' => (int) ($meetings->completed ?? 0),
                'open' => (int) ($meetings->total ?? 0) - (int) ($meetings->completed ?? 0),
                'upcoming' => (int) ($meetings->upcoming ?? 0),
            ],
        ];
    }

    /**
     * Tasks created per day for the last seven days, zero-filled so a quiet day
     * is a gap in the chart rather than a missing key.
     *
     * @return Collection<int, array{label: string, value: int, pct: int}>
     */
    protected function weeklyBars(Builder $query): Collection
    {
        $start = now()->startOfWeek();

        $counts = $query
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->whereDate('created_at', '>=', $start->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day');

        $rows = collect(range(0, 6))->map(function (int $offset) use ($start, $counts): array {
            $day = $start->copy()->addDays($offset);

            return [
                'label' => $day->format('D'),
                'value' => (int) ($counts[$day->toDateString()] ?? 0),
                'pct' => 0,
            ];
        });

        $max = max($rows->max('value'), 1);

        return $rows->map(fn (array $row): array => [
            'label' => $row['label'],
            'value' => $row['value'],
            'pct' => (int) round($row['value'] / $max * 100),
        ]);
    }
}
