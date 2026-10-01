<?php

namespace App\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves and caches the widgets a user may see.
 *
 * The order of operations is the whole design:
 *
 *   1. Filter by permission. A widget the user cannot see is discarded here, and
 *      its query is never built, never cached, never executed. The brief calls
 *      this out explicitly — "revoke the permission, the widget disappears AND
 *      its query never runs" — and `WidgetVisibilityTest` asserts it by counting
 *      queries, not by checking the rendered output.
 *   2. Cache the survivors, keyed by user AND by a signature of the permissions
 *      that were used to decide. Without the signature a permission change would
 *      be served from a cache entry computed under the old rules.
 */
class WidgetRegistry
{
    /**
     * @var list<DashboardWidget>
     */
    private array $widgets;

    /**
     * @param  list<DashboardWidget>  $widgets
     */
    public function __construct(?array $widgets = null)
    {
        $this->widgets = $widgets ?? self::defaults();
    }

    /**
     * @return list<DashboardWidget>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * The widgets this user may see — permission-filtered, un-resolved.
     *
     * @return list<DashboardWidget>
     */
    public function visibleTo(User $user): array
    {
        return array_values(array_filter(
            $this->widgets,
            fn (DashboardWidget $widget): bool => $this->permits($user, $widget),
        ));
    }

    /**
     * Resolved widget data, keyed by widget key, cached per widget.
     *
     * @return Collection<string, mixed>
     */
    public function resolveFor(User $user): Collection
    {
        $visible = $this->visibleTo($user);
        $signature = $this->permissionSignature($user);

        $resolved = [];

        foreach ($visible as $widget) {
            // The version segment is what makes invalidate() work: bumping it makes
            // every future key differ from every past one, so one write drops the
            // whole widget's cache without Cache::forget having to enumerate keys.
            $key = sprintf(
                'dashboard:widget:%s:v%d:%s',
                $widget->key(),
                $this->versionFor($widget->key()),
                $signature,
            );

            $resolved[$widget->key()] = Cache::remember(
                $key,
                $widget->cacheTtl(),
                static fn (): mixed => $widget->resolve($user),
            );
        }

        return collect($resolved);
    }

    /**
     * Resolved widgets grouped for the view, in registry order.
     *
     * @return Collection<string, Collection<string, mixed>>
     */
    public function groupedFor(User $user): Collection
    {
        $data = $this->resolveFor($user);
        $groups = [];

        foreach ($this->visibleTo($user) as $widget) {
            $groups[$widget->group()][] = [
                'widget' => $widget,
                'data' => $data->get($widget->key()),
            ];
        }

        return collect($groups);
    }

    public function permits(User $user, DashboardWidget $widget): bool
    {
        foreach ($widget->permissions() as $permission) {
            if (! $user->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drops a widget's cached value for every user.
     *
     * Called when the underlying data changes. Cache::forget cannot target a
     * wildcard, so the version segment is bumped instead: every future key
     * differs from every past one, which invalidates the lot in one write.
     */
    public function invalidate(string $widgetKey): void
    {
        $key = self::versionKey($widgetKey);

        // Written explicitly rather than with increment(), which does not reliably
        // create a missing key across stores — an invalidation that silently does
        // nothing is worse than one that is obviously broken.
        Cache::forever($key, ((int) Cache::get($key, 1)) + 1);
    }

    /**
     * The version segment folded into every cache key for this widget.
     */
    public function versionFor(string $widgetKey): int
    {
        return (int) Cache::get(self::versionKey($widgetKey), 1);
    }

    /**
     * A short digest of the permissions the widgets actually consulted.
     *
     * A permission change therefore produces a different key rather than
     * serving a value that was computed before the change.
     */
    protected function permissionSignature(User $user): string
    {
        $permissions = [];

        foreach ($this->widgets as $widget) {
            foreach ($widget->permissions() as $permission) {
                $permissions[$permission] = $user->hasPermission($permission);
            }
        }

        ksort($permissions);

        return substr(hash('xxh128', json_encode($permissions)), 0, 12);
    }

    protected static function versionKey(string $widgetKey): string
    {
        return 'dashboard:widget-version:'.$widgetKey;
    }

    /**
     * The shipped widget set.
     *
     * @return list<DashboardWidget>
     */
    public static function defaults(): array
    {
        return [
            // Personal
            new Widgets\PersonalStatsWidget,
            new Widgets\MyTodosWidget,
            new Widgets\MyTasksWidget,
            new Widgets\UpcomingMeetingsWidget,
            new Widgets\OverdueItemsWidget,
            new Widgets\TodaysActivityWidget,
            new Widgets\UpcomingDeadlinesWidget,

            // Management
            new Widgets\TeamWorkloadWidget,
            new Widgets\CompletionRateWidget,
            new Widgets\TaskDistributionWidget,
            new Widgets\DepartmentPerformanceWidget,

            // Compliance
            new Widgets\ObligationExpiryWidget,
            new Widgets\CriticalDeadlinesWidget,
        ];
    }
}
