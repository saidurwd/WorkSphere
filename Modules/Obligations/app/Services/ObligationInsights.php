<?php

namespace Modules\Obligations\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;

/**
 * The numbers behind the obligation overview and reports.
 *
 * Every figure is computed over {@see Obligation::scopeVisibleTo()}, so a user sees
 * counts, type breakdowns, vendor spend and risk lists only for obligations they may
 * open. The Blade versions leaked in three places: the priority and type breakdowns,
 * the overdue-task count, and a risk list whose `OR` escaped the visibility filter.
 */
class ObligationInsights
{
    private const CLOSED = ['renewed', 'cancelled', 'not_required', 'archived'];

    /**
     * @return Builder<Obligation>
     */
    protected function visible(User $user): Builder
    {
        return Obligation::query()->visibleTo($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $base = fn (): Builder => $this->visible($user);
        $today = now()->startOfDay();
        $expired = fn (): Builder => $base()->where('expiry_date', '<', $today)->whereNotIn('status', self::CLOSED);

        return [
            'stats' => [
                'Active' => $base()->where('status', 'active')->count(),
                'Due in 7 days' => $base()->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)])->count(),
                'Due in 30 days' => $base()->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])->count(),
                'Expired' => $expired()->count(),
                'Critical risk' => $base()->where('risk_level', 'critical')->count(),
                'High risk' => $base()->where('risk_level', 'high')->count(),
                'Renewal in progress' => $base()->where('status', 'renewal_in_progress')->count(),
                'Pending approval' => $base()->where('status', 'pending_approval')->count(),
                'Overdue tasks' => $this->overdueTasks($user, $today),
            ],
            'lists' => [
                'Next to expire' => $base()->orderBy('expiry_date')->limit(10)->get(),
                'Critical risk' => $base()->where('risk_level', 'critical')->orderBy('expiry_date')->limit(10)->get(),
                'Expired' => $expired()->orderBy('expiry_date')->limit(10)->get(),
            ],
            'typeBars' => $this->typeBars($user),
            'priorityBars' => $this->priorityBars($user),
        ];
    }

    protected function overdueTasks(User $user, Carbon $today): int
    {
        return Task::query()
            ->whereNotNull('obligation_id')
            ->whereIn('obligation_id', $this->visible($user)->select('obligations.id'))
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', $today)
            ->count();
    }

    /**
     * @return list<array{label: string, value: int, pct: int}>
     */
    protected function typeBars(User $user): array
    {
        $rows = $this->visible($user)
            ->join('obligation_types', 'obligations.obligation_type_id', '=', 'obligation_types.id')
            ->selectRaw('obligation_types.type_name as label, COUNT(obligations.id) as total')
            ->groupBy('obligation_types.type_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return $this->bars($rows);
    }

    /**
     * @return list<array{label: string, value: int, pct: int}>
     */
    protected function priorityBars(User $user): array
    {
        $rows = $this->visible($user)
            ->selectRaw('priority as label, COUNT(*) as total')
            ->groupBy('priority')
            ->get()
            ->map(function ($row) {
                $row->label = ucfirst((string) $row->label);

                return $row;
            });

        return $this->bars($rows);
    }

    /**
     * @param  iterable<int, object{label: string, total: int|string}>  $rows
     * @return list<array{label: string, value: int, pct: int}>
     */
    protected function bars(iterable $rows): array
    {
        $rows = collect($rows);
        $max = max((int) $rows->max('total'), 1);

        return $rows->map(fn ($row): array => [
            'label' => (string) $row->label,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $max * 100),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(User $user): array
    {
        $today = now()->startOfDay();

        $expiryByType = $this->visible($user)
            ->join('obligation_types', 'obligations.obligation_type_id', '=', 'obligation_types.id')
            ->selectRaw('obligation_types.type_name as label, COUNT(obligations.id) as total')
            ->whereBetween('obligations.expiry_date', [$today, $today->copy()->addDays(90)])
            ->groupBy('obligation_types.type_name')
            ->orderByDesc('total')
            ->get();

        $departments = $this->visible($user)
            ->join('departments', 'obligations.department_id', '=', 'departments.id')
            ->selectRaw('departments.department_name as label, COUNT(obligations.id) as total, SUM(CASE WHEN obligations.expiry_date < ? THEN 1 ELSE 0 END) as expired, SUM(CASE WHEN obligations.risk_level = ? THEN 1 ELSE 0 END) as critical', [$today, 'critical'])
            ->groupBy('departments.department_name')
            ->orderByDesc('total')
            ->get();

        $vendors = $this->visible($user)
            ->join('vendors', 'obligations.vendor_id', '=', 'vendors.id')
            ->selectRaw('vendors.vendor_name as label, COUNT(obligations.id) as total, SUM(obligations.estimated_cost) as total_cost')
            ->groupBy('vendors.vendor_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $leadTime = match (DB::connection()->getDriverName()) {
            'sqlite' => 'AVG(CAST(julianday(obligations.updated_at) - julianday(obligations.start_date) AS INTEGER))',
            default => 'AVG(DATEDIFF(obligations.updated_at, obligations.start_date))',
        };

        $renewal = $this->visible($user)
            ->selectRaw("SUM(CASE WHEN obligations.status IN ('active', 'renewed') AND obligations.expiry_date >= ? THEN 1 ELSE 0 END) as on_time, SUM(CASE WHEN obligations.status IN ('active', 'renewed') AND obligations.expiry_date < ? THEN 1 ELSE 0 END) as late, SUM(CASE WHEN obligations.status = 'expired' THEN 1 ELSE 0 END) as expired_count, {$leadTime} as avg_lead_time", [$today, $today])
            ->first();

        // The OR belongs INSIDE the visibility filter. Chained onto it, it widened
        // the list to every high-risk or lapsed obligation in the system.
        $risk = $this->visible($user)
            ->where(function (Builder $either) use ($today): void {
                $either->whereIn('risk_level', ['critical', 'high'])
                    ->orWhere(fn (Builder $lapsed): Builder => $lapsed
                        ->where('expiry_date', '<', $today)
                        ->whereNotIn('status', self::CLOSED));
            })
            ->orderByDesc('risk_level')
            ->orderBy('expiry_date')
            ->limit(20)
            ->get();

        return [
            'expiryByType' => $this->bars($expiryByType),
            'departments' => $departments,
            'vendors' => $vendors,
            'renewal' => $renewal,
            'risk' => $risk,
        ];
    }
}
