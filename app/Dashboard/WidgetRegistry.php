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
 *      its query is never built, never cached, never executed. The brief calls this
 *      out explicitly — "revoke the permission, the widget disappears AND its
 *      query never runs" — and `WidgetVisibilityTest` asserts it by counting
 *      queries, not by checking the rendered output.
 *   2. Cache the survivors, keyed by user AND by a signature of the permissions
 *      that were used to decide. Without the signature a permission change would be
 *      served from a cache entry computed under the old rules; without the user id
 *      two users would share one entry, which for a personal widget means one
 *      user's work items rendered on another's dashboard.
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
            //
            // The user id is the load-bearing part and it was MISSING until Phase
            // 14. Keying only on the widget and a digest of the permissions meant
            // two users holding the same permissions shared one entry — and the
            // personal widgets (`my_todos`, `my_tasks`, `personal_stats`) resolve
            // data ABOUT THE VIEWER, so Alice's To-Dos were rendered on Bob's
            // dashboard. The permission signature cannot prevent that: it is
            // identical for two users who can see the same widgets.
            //
            // Both segments are kept. The user id separates people; the signature
            // handles the other axis — a user whose OWN permissions changed must
            // not be served a value computed under the old set.
            //
            // Stored through `cacheable()`: eleven of the thirteen widgets resolve
            // to a Collection, and an object in the cache comes back from the
            // database store as `__PHP_Incomplete_Class` under the
            // `allowed_classes => false` in `config/cache.php` — so the second
            // dashboard view of any user threw on the first method call. The array
            // driver in `phpunit.xml` hands back the same object it was given, which
            // is why the suite never saw it.
            $resolved[$widget->key()] = $this->hydrate($widget, Cache::remember(
                $this->keyFor($user, $widget),
                $widget->cacheTtl(),
                fn (): mixed => $this->cacheable($widget->resolve($user)),
            ));
        }

        return collect($resolved);
    }

    /**
     * Reduce a resolved value to something the cache can hold.
     *
     * Recurses, because a widget that returns an ARRAY can still contain a
     * Collection inside it: `obligation_expiry` returns `['typeBars' => Collection,
     * 'priorityDonut' => Collection, ...]` and `personal_stats` nests one under
     * `weeklyBars`. Handling only the top level left those nested objects in the
     * cache, so the second dashboard view still threw — on
     * `$data->get('obligation_expiry.priorityDonut')->sum()` in
     * `DashboardController`.
     *
     * Nothing else is expected: every widget aggregates in SQL and returns rows
     * already mapped to arrays, so there is no model to flatten and the
     * conversion stays lossless. A model reaching here would be a widget
     * regression, so it is left intact rather than silently blanked — the
     * round-trip test is what catches that.
     */
    protected function cacheable(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            return array_map(
                fn (mixed $item): mixed => $this->cacheable($item),
                $value->all(),
            );
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->cacheable($item), $value);
        }

        return $value;
    }

    /**
     * Put a cached value back into the shape the view expects.
     *
     * A widget that resolves to a Collection is cached as a plain array — an
     * object in the cache comes back from the database store as
     * `__PHP_Incomplete_Class` under the `allowed_classes => false` in
     * `config/cache.php` — so it has to be re-wrapped on the way out. The two
     * widgets that resolve to a keyed stat map are left as arrays, because
     * wrapping those would turn a map into a list and break the `[$key]` lookups
     * in the partials.
     */
    protected function hydrate(DashboardWidget $widget, mixed $cached): mixed
    {
        if (! is_array($cached)) {
            return $cached;
        }

        if ($widget->isListValued()) {
            // The rows are already arrays of scalars, so the outer level is all
            // that needs rebuilding. Recursing into them would wrap any
            // list-shaped ROW in a Collection and corrupt the list.
            return new Collection($cached);
        }

        // A keyed stat map, whose values may themselves be the chart Collections
        // that `cacheable()` flattened. `DashboardController` calls `->sum()` and
        // `->all()` on those directly, so they have to come back as Collections.
        return array_map(
            fn (mixed $value): mixed => $this->restoreChart($value),
            $cached,
        );
    }

    /**
     * Rebuild a chart Collection that `cacheable()` flattened into a list.
     *
     * A non-empty list becomes a Collection; a keyed stat map such as
     * `personal_stats.tasks` and a scalar both pass through untouched, because
     * their callers index or compare them directly. Empty is left alone too —
     * `array_is_list([])` is true, but there is nothing to rebuild and a caller
     * expecting an array would be handed a Collection.
     */
    protected function restoreChart(mixed $value): mixed
    {
        return is_array($value) && $value !== [] && array_is_list($value)
            ? new Collection($value)
            : $value;
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

    /**
     * The cache key a widget's value is stored under for a given user.
     *
     * Public so the key's SHAPE can be asserted by a test rather than inferred
     * from the store's internals. A key nobody can name is a key nobody can notice
     * losing its user id — which is precisely how the leak this method now makes
     * checkable got in.
     */
    public function keyFor(User $user, DashboardWidget $widget): string
    {
        return sprintf(
            'dashboard:widget:%s:u%d:v%d:%s',
            $widget->key(),
            $user->getKey(),
            $this->versionFor($widget->key()),
            $this->permissionSignature($user),
        );
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
