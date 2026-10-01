<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Dashboard\Widgets\TaskDistributionWidget as TaskDistributionWidgetAlias;
use App\Models\User;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Obligation expiry exposure: active, expiring soon, and already lapsed.
 *
 * Compliance, so it is gated on the compliance permission rather than on the
 * general obligation permission: someone who can read obligations for their own
 * work still does not get an organisation-wide expiry picture.
 */
class ObligationExpiryWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'obligation_expiry';
    }

    public function label(): string
    {
        return 'Obligation Expiry';
    }

    public function icon(): string
    {
        return 'file-earmark-text';
    }

    public function group(): string
    {
        return 'compliance';
    }

    public function permissions(): array
    {
        return ['obligation.view', 'obligation.view_reports'];
    }

    public function cacheTtl(): int
    {
        return 300;
    }

    /**
     * Four conditional counts over one pass of the visible obligations: active,
     * due within 7 and 30 days, and already past their expiry date.
     */
    public function resolve(User $user): array
    {
        $today = now()->toDateString();
        $in7 = now()->addDays(7)->toDateString();
        $in30 = now()->addDays(30)->toDateString();

        $row = $this->visibleObligations($user)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active', ['active'])
            ->selectRaw('SUM(CASE WHEN expiry_date BETWEEN ? AND ? THEN 1 ELSE 0 END) AS due_7', [$today, $in7])
            ->selectRaw('SUM(CASE WHEN expiry_date BETWEEN ? AND ? THEN 1 ELSE 0 END) AS due_30', [$today, $in30])
            ->selectRaw('SUM(CASE WHEN expiry_date < ? AND status NOT IN (?, ?, ?, ?) THEN 1 ELSE 0 END) AS expired', [
                $today, ...$this->closedObligationStatuses(),
            ])
            ->first();

        return [
            // Two charts the existing dashboard renders from obligations. Both are
            // grouped aggregates; neither loads rows to count them.
            'typeBars' => $this->typeBars($this->visibleObligations($user)),
            'priorityDonut' => $this->priorityDonut($this->visibleObligations($user)),
            'total' => (int) ($row->total ?? 0),
            'active' => (int) ($row->active ?? 0),
            'due_7' => (int) ($row->due_7 ?? 0),
            'due_30' => (int) ($row->due_30 ?? 0),
            'expired' => (int) ($row->expired ?? 0),
        ];
    }

    /**
     * Active obligations per type, capped, scaled to the largest.
     *
     * @return Collection<int, array{label: string, value: int, pct: int, color: string}>
     */
    protected function typeBars(Builder $query): Collection
    {
        $rows = $query
            ->join('obligation_types', 'obligation_types.id', '=', 'obligations.obligation_type_id')
            ->whereNotIn('obligations.status', $this->closedObligationStatuses())
            ->groupBy('obligation_types.id', 'obligation_types.type_name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get(['obligation_types.type_name', DB::raw('COUNT(*) AS total')]);

        $max = (int) max($rows->max('total') ?: 0, 1);

        return $rows->map(fn ($row): array => [
            'label' => (string) $row->type_name,
            'value' => (int) $row->total,
            'pct' => (int) round(((int) $row->total / $max) * 100),
            'color' => 'var(--bs-info)',
        ]);
    }

    /**
     * Obligations per priority band.
     *
     * @return Collection<int, array{label: string, value: int, count: int, pct: int, color: string}>
     */
    protected function priorityDonut(Builder $query): Collection
    {
        $rows = $query
            ->groupBy('priority')
            ->get(['priority', DB::raw('COUNT(*) AS total')]);

        $total = (int) $rows->sum('total');

        return $rows->map(fn ($row): array => [
            'label' => ucfirst((string) $row->priority),
            'value' => (int) $row->total,
            'count' => (int) $row->total,
            'pct' => $total === 0 ? 0 : (int) round(((int) $row->total / $total) * 100),
            'color' => TaskDistributionWidgetAlias::colorFor(StatusBadge::priorityVariant($row->priority)),
        ]);
    }
}
