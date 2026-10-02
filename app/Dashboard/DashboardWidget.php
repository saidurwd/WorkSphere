<?php

namespace App\Dashboard;

use App\Models\User;

/**
 * One dashboard panel.
 *
 * A widget owns its own query and its own permission, so a dashboard is the sum
 * of independently-gated pieces rather than one screen that either appears or
 * does not. That is what lets a user without `obligation.view` still see their
 * tasks, without the obligations panel ever running a query.
 *
 * Every implementation must:
 *
 * - aggregate in SQL. A widget that loads rows to count them is a bug, and at
 *   dashboard scale it is the bug that makes the whole page slow.
 * - explain its query in one sentence in its `resolve()` docblock.
 * - eager-load anything it renders. N+1 on the dashboard is felt on every page.
 */
interface DashboardWidget
{
    /**
     * Stable identifier, also used as the cache-key segment.
     */
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    /**
     * The permissions required to see this widget. ALL of them.
     *
     * Permission-driven by design: there is no role check anywhere in this
     * namespace. A widget is visible because the user holds the permission that
     * governs the data it would show, not because their role has a slug.
     *
     * @return list<string>
     */
    public function permissions(): array;

    /**
     * The widget's data. Only called when `isVisibleTo()` has already passed, so
     * a hidden widget's query never runs at all.
     */
    public function resolve(User $user): mixed;

    /**
     * Whether `resolve()` returns a LIST of rows rather than a keyed map.
     *
     * Exists because the registry cannot cache a Collection: an object in the cache
     * comes back from the database store as `__PHP_Incomplete_Class` under the
     * `allowed_classes => false` in `config/cache.php`, and the first method call on
     * it throws. So a list is stored as a plain array and re-wrapped in a Collection
     * on the way out, and a keyed map is stored and returned as an array.
     *
     * A DECLARED answer rather than one inferred. Inferring it from the payload
     * cannot work — a stat map and a list of rows are both arrays, and an empty map
     * is indistinguishable from an empty list — and inferring it from `resolve()`
     * would mean running every widget's query a second time on every dashboard
     * view, which is precisely the work the cache exists to avoid.
     */
    public function isListValued(): bool;

    /**
     * The layout group this widget belongs to.
     */
    public function group(): string;

    /**
     * How long the resolved value may be cached, in seconds.
     *
     * Short by default. A stale dashboard is a minor annoyance; a stale
     * *permission* is not, which is why the cache key includes the permission
     * signature rather than only the user id.
     */
    public function cacheTtl(): int;
}
