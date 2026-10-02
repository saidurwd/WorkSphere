<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The nearest critical-risk obligations, whatever their expiry.
 *
 * Compliance-gated. `risk_level` is a separate vocabulary from `priority` and
 * has no shared enum, so it is matched on its literal values here rather than
 * through StatusBadge.
 */
class CriticalDeadlinesWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'critical_deadlines';
    }

    public function label(): string
    {
        return 'Critical Deadlines';
    }

    public function icon(): string
    {
        return 'exclamation-octagon';
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
     * High or critical risk obligations that are still open, soonest expiry
     * first, capped. The status exclusion list is the same one every other
     * obligation query uses.
     */
    public function resolve(User $user): Collection
    {
        return $this->visibleObligations($user)
            ->whereIn('risk_level', ['critical', 'high'])
            ->whereNotIn('status', $this->closedObligationStatuses())
            ->orderByRaw('expiry_date IS NULL, expiry_date')
            ->limit(8)
            ->get(['id', 'title', 'expiry_date', 'risk_level', 'owner_user_id'])
            ->map(fn ($obligation): array => [
                'id' => $obligation->id,
                'title' => $obligation->title,
                'risk' => ucfirst((string) $obligation->risk_level),
                'risk_variant' => $obligation->risk_level === 'critical' ? 'danger' : 'warning',
                'expiry' => $obligation->expiry_date?->format('M d, Y'),
                'days' => $obligation->expiry_date === null
                    ? null
                    : (int) now()->startOfDay()->diffInDays($obligation->expiry_date->startOfDay(), false),
                'url' => route('obligations.show', $obligation->id),
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
