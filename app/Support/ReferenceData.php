<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Obligations\Models\Vendor;

/**
 * The reference lists every dropdown and filter needs.
 *
 * Thirty-two call sites across ten controllers were issuing the same two queries —
 * `User::orderBy('name')->get(['id', 'name'])` and
 * `Department::orderBy('department_name')->get()` — once per page, sometimes twice
 * on the same page. Not an N+1 and not slow in isolation: the measurement in
 * `PerformanceBaselineTest` shows each query costing 1–3 ms and running exactly
 * once per request. This exists to remove thirty-two duplicated statements and one
 * more round trip each time a page composes several of them, not because a query
 * was slow.
 *
 * INVALIDATION is by version bump, not `Cache::forget`: the store cannot target a
 * wildcard, so every write to a reference table bumps a counter that is folded into
 * every future key. One write drops the lot, and a key can never be resurrected
 * with a stale value because the version it was written under no longer matches.
 *
 * ON SHARED KEYS: these lists are deliberately NOT keyed per user, because they are
 * identical for everybody — `User::orderBy('name')->get()` returns the same rows to
 * every caller. That is exactly what makes this safe and what makes it different
 * from the dashboard widget cache, where a shared key leaked one user's personal
 * To-Dos to another. A caller that needs a PERMISSION-FILTERED list must pass a
 * scope, which becomes part of the key: a filtered variant and the full list can
 * never collide, so a future filtered caller cannot accidentally read the
 * unfiltered cached value.
 *
 * WHAT IS STORED: an array of attribute arrays, never models. The database store
 * unserializes with `allowed_classes => false` (see `config/cache.php`), so a
 * cached model comes back as `__PHP_Incomplete_Class` and the first property read
 * on it throws. See {@see remember()} for the full account.
 */
class ReferenceData
{
    /**
     * How long a reference list stays cached before it is rebuilt regardless.
     *
     * A safety net, not the invalidation strategy: a write bumps the version
     * immediately, so this only bounds how stale the cache can be if an observer is
     * ever unregistered and nothing else notices.
     */
    private const TTL_SECONDS = 300;

    /**
     * The only two scopes a list may be requested in. A closed set on purpose —
     * see {@see remember()}.
     */
    public const SCOPE_ALL = 'all';

    public const SCOPE_ACTIVE = 'active';

    /**
     * @return Collection<int, User>
     */
    public function users(string $scope = 'all'): Collection
    {
        return $this->remember(
            'users',
            $scope,
            fn (): Collection => $this->query(User::query(), 'name', $scope, ['id', 'name']),
            User::class,
        );
    }

    /**
     * @return Collection<int, Department>
     */
    public function departments(string $scope = 'all'): Collection
    {
        return $this->remember(
            'departments',
            $scope,
            fn (): Collection => $this->query(Department::query(), 'department_name', $scope),
            Department::class,
        );
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(string $scope = 'all'): Collection
    {
        return $this->remember(
            'locations',
            $scope,
            fn (): Collection => $this->query(Location::query(), 'location_name', $scope),
            Location::class,
        );
    }

    /**
     * @return Collection<int, Vendor>
     */
    public function vendors(string $scope = 'all'): Collection
    {
        return $this->remember(
            'vendors',
            $scope,
            fn (): Collection => $this->query(Vendor::query(), 'vendor_name', $scope),
            Vendor::class,
        );
    }

    /**
     * @return Collection<int, Company>
     */
    public function companies(string $scope = 'all'): Collection
    {
        return $this->remember(
            'companies',
            $scope,
            fn (): Collection => $this->query(Company::query(), 'company_name', $scope),
            Company::class,
        );
    }

    /**
     * Drop every cached list, or just one resource's.
     *
     * Called by the observer on write. Bumps a version rather than deleting, so it
     * is O(1) regardless of how many keys exist.
     */
    public function invalidate(?string $resource = null): void
    {
        $key = $this->versionKey($resource ?? '*');

        // Written explicitly rather than with increment(), which does not reliably
        // create a missing key across stores — an invalidation that silently does
        // nothing is worse than one that is obviously broken.
        $this->store()->forever($key, ((int) $this->store()->get($key, 1)) + 1);
    }

    /**
     * The cache key a resource's list is stored under. Exposed so a test can assert
     * its shape, for the same reason `WidgetRegistry::keyFor()` is public.
     */
    public function keyFor(string $resource, string $scope = 'all'): string
    {
        return sprintf(
            'reference:%s:v%d:%s',
            $resource,
            (int) $this->store()->get($this->versionKey($resource), 1),
            $scope,
        );
    }

    /**
     * @param  callable(): Collection<int, Model>  $query
     * @param  class-string<Model>  $model
     * @return Collection<int, Model>
     */
    private function remember(string $resource, string $scope, callable $query, string $model): Collection
    {
        // An unknown scope is a programming error, not a cache detail. Left
        // unchallenged it would store the UNFILTERED list under a key that reads as
        // filtered, and a caller expecting a narrower list would silently get a
        // wider one — which shows up later as unexpected data in a dropdown rather
        // than as a failure here.
        if (! in_array($scope, [self::SCOPE_ALL, self::SCOPE_ACTIVE], true)) {
            throw new \InvalidArgumentException(
                "Unknown reference scope [{$scope}] for [{$resource}]. Expected "
                .self::SCOPE_ALL.' or '.self::SCOPE_ACTIVE.'.',
            );
        }

        $cached = $this->store()->remember(
            $this->keyFor($resource, $scope),
            self::TTL_SECONDS,
            // A list of ARRAYS, never a Collection of models.
            //
            // `config('cache.serializable_classes')` is `false`, so the database
            // store calls `unserialize($value, ['allowed_classes' => false])`, and
            // PHP answers that with a `__PHP_Incomplete_Class` for every object it
            // finds. Caching the models therefore produced a cache that was
            // permanently poisoned: every subsequent read returned seven
            // `__PHP_Incomplete_Class` objects instead of seven users, and the
            // first view to read `$user->id` threw
            // "Attempt to read property id on __PHP_Incomplete_Class" — on
            // `/tasks/create`, `/meetings/create`, every dropdown.
            //
            // It was invisible to the suite because `phpunit.xml` sets
            // `CACHE_STORE=array`, and the array store never unserializes: it
            // hands back the very object that was put in. The bug needed a
            // persistent, serializing store to exist at all, and MySQL is what
            // production runs.
            //
            // Storing scalars is also the right shape independent of the policy.
            // These are `id`/`name` pairs used to render an `<option>`; a cache
            // entry that has to reconstruct a hydrated model to yield two strings
            // is carrying a live object graph — and a stale one, since a cached
            // model keeps attributes the database has since changed. Arrays cannot
            // drift, and rehydration below rebuilds the models fresh every read.
            fn (): array => $query()->map(fn (Model $row): array => $row->attributesToArray())->all(),
        );

        return $this->hydrate($cached, $model);
    }

    /**
     * Rebuild the models a cached array of attributes describes.
     *
     * `newFromBuilder()` rather than `newInstance()`: the rows came from a query,
     * so they exist and are hydrated. Setting the connection and table explicitly
     * is what makes the models behave like the ones they replaced — `newFromBuilder`
     * leaves a fresh model without them.
     *
     * @param  iterable<int, array<string, mixed>>|mixed  $cached
     * @param  class-string<Model>  $model
     * @return Collection<int, Model>
     */
    private function hydrate(mixed $cached, string $model): Collection
    {
        // Defensive: a cache written by an older build, or by a store that hands
        // back whatever was put in, may not be the array shape this expects. Treat
        // anything else as a miss and rebuild rather than failing the render.
        if (! is_iterable($cached)) {
            $cached = $this->query(new $model, 'id', self::SCOPE_ALL)->all();
        }

        $connection = (new $model)->getConnection();
        $table = (new $model)->getTable();

        $rows = [];

        foreach ($cached as $attributes) {
            $instance = (new $model)->newFromBuilder((array) $attributes);
            $instance->setConnection($connection->getName());
            $instance->setTable($table);
            $rows[] = $instance;
        }

        return new Collection($rows);
    }

    /**
     * Apply the scope and the ordering to a list query.
     *
     * `users` and `departments` have no `status` column, so `active` is rejected for
     * them rather than silently ignored: asking for a filtered list of a table that
     * cannot be filtered is a mistake worth surfacing at the call site.
     *
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     * @return Collection<int, Model>
     */
    private function query(Builder $query, string $orderBy, string $scope, array $columns = ['*']): Collection
    {
        if ($scope === self::SCOPE_ACTIVE) {
            if (! in_array('status', $query->getModel()->getFillable(), true)
                && ! Schema::hasColumn($query->getModel()->getTable(), 'status')) {
                throw new \InvalidArgumentException(
                    "Cannot scope [{$query->getModel()->getTable()}] to active: it has no status column.",
                );
            }

            $query->where('status', 'active');
        }

        return $query->orderBy($orderBy)->get($columns);
    }

    private function versionKey(string $resource): string
    {
        return 'reference:version:'.$resource;
    }

    private function store(): Repository
    {
        return Cache::store();
    }
}
