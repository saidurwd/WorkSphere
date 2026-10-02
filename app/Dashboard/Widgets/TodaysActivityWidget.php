<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What happened today across every module.
 *
 * Reads the shared `activity_logs` table, so it is one query rather than one
 * per module — and it picks up modules automatically as they start writing there.
 */
class TodaysActivityWidget implements DashboardWidget
{
    public function key(): string
    {
        return 'todays_activity';
    }

    public function label(): string
    {
        return "Today's Activity";
    }

    public function icon(): string
    {
        return 'clock-history';
    }

    public function group(): string
    {
        return 'personal';
    }

    /**
     * Empty on purpose. This panel reads the viewer's own trail rather than other
     * people's data, so it needs no permission and leaks nothing: the WHERE clause
     * is `user_id = ?`.
     */
    public function permissions(): array
    {
        return [];
    }

    public function cacheTtl(): int
    {
        return 60;
    }

    /**
     * The ten most recent activity rows the viewer authored today, newest first.
     */
    public function resolve(User $user): Collection
    {
        return ActivityLog::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->latest('id')
            ->limit(10)
            ->get(['id', 'module_name', 'action', 'created_at'])
            ->map(fn (ActivityLog $entry): array => [
                'module' => class_basename((string) $entry->module_name),
                'action' => str_replace('_', ' ', (string) $entry->action),
                'when' => $entry->created_at?->diffForHumans(),
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
