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
        return $this->remember('users', $scope, fn (): Collection => $this->query(User::query(), 'name', $scope, ['id', 'name']));
    }

    /**
     * @return Collection<int, Department>
     */
    public function departments(string $scope = 'all'): Collection
    {
        return $this->remember('departments', $scope, fn (): Collection => $this->query(Department::query(), 'department_name', $scope));
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(string $scope = 'all'): Collection
    {
        return $this->remember('locations', $scope, fn (): Collection => $this->query(Location::query(), 'location_name', $scope));
    }

    /**
     * @return Collection<int, Vendor>
     */
    public function vendors(string $scope = 'all'): Collection
    {
        return $this->remember('vendors', $scope, fn (): Collection => $this->query(Vendor::query(), 'vendor_name', $scope));
    }

    /**
     * @return Collection<int, Company>
     */
    public function companies(string $scope = 'all'): Collection
    {
        return $this->remember('companies', $scope, fn (): Collection => $this->query(Company::query(), 'company_name', $scope));
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
     * @return Collection<int, Model>
     */
    private function remember(string $resource, string $scope, callable $query): Collection
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

        // `all()` rather than the raw cache value: an unserialised Eloquent
        // Collection comes back from some stores as a plain array, and a view
        // calling `->first()` on it would then fail at render time.
        $cached = $this->store()->remember(
            $this->keyFor($resource, $scope),
            self::TTL_SECONDS,
            $query,
        );

        return $cached instanceof Collection ? $cached : new Collection($cached);
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
