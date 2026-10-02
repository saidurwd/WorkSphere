<?php

namespace App\Support;

use App\Enums\Role as RoleSlug;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Resolves the sidebar tree for the user looking at it.
 *
 * The filtering used to live in `sidebar.blade.php`, which meant the rules could
 * only be exercised by rendering a page — so "is this node visible?" was a
 * question about markup rather than about code. Moving it here makes it testable
 * directly and keeps the Blade to rendering.
 *
 * Four behaviours, each of which the previous tree got wrong in a different way:
 *
 * 1. **Permission gating.** A node the caller cannot use is removed, not greyed
 *    out. A menu item that leads to a 403 is worse than no item: it advertises a
 *    capability the account does not have.
 * 2. **Empty branches are pruned.** Hiding every child of a parent and leaving the
 *    parent as an expandable heading containing nothing is the single most common
 *    way a permission-filtered menu ends up looking broken. Pruning happens
 *    bottom-up, after children are filtered.
 * 3. **Parameter-aware active state.** The four identity Reference Data screens are
 *    one route with a `{resource}` parameter. Without parameter matching, visiting
 *    Employees highlights all four at once.
 * 4. **A missing route does not take the page down.** A nav entry naming a route
 *    that has since been renamed would throw while rendering the layout, breaking
 *    every page including the 404. An unreachable node is dropped instead.
 *
 * Two role keys, because the application has two role levels and a node has to be
 * able to say which one it means: `admin` accepts anything in
 * `config('authorization.admin_roles')`, and `super_admin` names the one slug the
 * database-backup screen is restricted to.
 */
class NavigationMenu
{
    public function __construct(private readonly Request $request) {}

    /**
     * The menu for a user, already filtered and pruned.
     *
     * @return list<array<string, mixed>>
     */
    public function forUser(?User $user): array
    {
        return $this->prune($this->filter((array) config('navigation.menu', []), $user));
    }

    /**
     * The section headings, in the order they first appear, skipping ones whose
     * nodes were all filtered away.
     *
     * A heading with nothing under it is worse than no heading — it is a rule on
     * the page with no label.
     *
     * @param  list<array<string, mixed>>  $menu
     * @return list<string>
     */
    public function sections(array $menu): array
    {
        $sections = [];

        foreach ($menu as $node) {
            if (! empty($node['section'])) {
                $sections[(string) $node['section']] = true;
            }
        }

        return array_keys($sections);
    }

    public function url(array $node): ?string
    {
        $route = $node['route'] ?? null;

        if (! is_string($route) || $route === '') {
            return null;
        }

        if (! Route::has($route)) {
            return null;
        }

        return route($route, (array) ($node['params'] ?? []));
    }

    /**
     * Whether a node is the current location.
     *
     * A branch is active when a descendant is, so the whole path stays open while
     * you are inside it.
     *
     * @param  array<string, mixed>  $node
     */
    public function isActive(array $node): bool
    {
        if ($this->matches($node)) {
            return true;
        }

        foreach ((array) ($node['children'] ?? []) as $child) {
            if ($this->isActive($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a leaf is the current location — as opposed to being on a path that
     * leads to it.
     *
     * @param  array<string, mixed>  $node
     */
    public function isCurrent(array $node): bool
    {
        return empty($node['children']) && $this->matches($node);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function matches(array $node): bool
    {
        $route = $this->request->route();

        if ($route === null) {
            return false;
        }

        $patterns = (array) ($node['active'] ?? []);

        if ($patterns === [] && ! empty($node['route'])) {
            $patterns = [$node['route']];
        }

        $patterns = array_filter($patterns);

        if ($patterns === [] || ! $this->request->routeIs(...$patterns)) {
            return false;
        }

        // Parameter matching is what separates the four Reference Data screens,
        // which share one route name and differ only by `{resource}`.
        foreach ((array) ($node['params'] ?? []) as $key => $value) {
            if ((string) $route->parameter($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    protected function filter(array $nodes, ?User $user): array
    {
        $isAdmin = $user?->hasAnyRole((array) config('authorization.admin_roles', [])) ?? false;
        $isSuperAdmin = $user?->hasRole(RoleSlug::SuperAdmin->value) ?? false;

        $kept = [];

        foreach ($nodes as $node) {
            if (($node['admin'] ?? false) && ! $isAdmin) {
                continue;
            }

            // A screen restricted to super-admin BY ROLE, not by permission. A
            // database backup is the entire system — credentials, personal data,
            // hashes — and `DatabaseBackupController` checks the role deliberately:
            // a permission would be as broad as whatever grants permissions, so
            // anyone who could hand out permissions could hand out backups.
            //
            // Without this the node can only be gated on a permission the
            // controller ignores, which is how `Database Backups` came to be
            // advertised to every `admin` and refused to all of them.
            if (($node['super_admin'] ?? false) && ! $isSuperAdmin) {
                continue;
            }

            if (! $this->permits($node, $user)) {
                continue;
            }

            // A node whose route has been renamed is dropped rather than rendered:
            // `route()` would throw inside the layout and take every page down.
            if (! empty($node['children']) || $this->url($node) !== null) {
                $wasBranch = ($node['children'] ?? []) !== [];

                $node['children'] = $this->filter((array) ($node['children'] ?? []), $user);

                // A branch whose children were ALL filtered away goes with them.
                //
                // This cannot be left to `prune()`, which runs afterwards: by then
                // `children` has been overwritten with `[]`, which is exactly what a
                // LEAF looks like, so the branch survives as a disclosure containing
                // nothing — a dead row in the sidebar that looks like a rendering
                // bug. It is the single most common way a permission-filtered menu
                // ends up appearing broken.
                if ($wasBranch && $node['children'] === []) {
                    continue;
                }

                $kept[] = $node;
            }
        }

        return $kept;
    }

    /**
     * Remove branches left with no children, bottom-up.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    protected function prune(array $nodes): array
    {
        $kept = [];

        foreach ($nodes as $node) {
            $hadChildren = ($node['children'] ?? []) !== [];
            $children = $this->prune((array) ($node['children'] ?? []));

            if ($hadChildren) {
                // A branch whose children were all filtered away goes with them. An
                // expandable heading containing nothing is the most common way a
                // permission-filtered menu ends up looking broken.
                if ($children === []) {
                    continue;
                }

                $node['children'] = $children;
            }

            $kept[] = $node;
        }

        return $kept;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function permits(array $node, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        /**
         * A super-admin sees everything, and that is not a shortcut.
         *
         * `Gate::before` returns true for a super-admin on EVERY ability, so a menu
         * that hides a node from them advertises less than the enforcement layer
         * permits — the menu and the application disagree about the same account.
         * The menu must not hide what the Gate allows, or an operator concludes the
         * feature is missing when it is merely unseeded.
         *
         * Role-gated nodes (`super_admin`) are still enforced separately above; this
         * covers PERMISSION-gated ones, which is where the drift appeared: the
         * `system.*` permissions were added to the catalogue but not yet granted to
         * an existing database, and the whole System section vanished for the very
         * person who could most use it.
         */
        if ($user->hasRole(RoleSlug::SuperAdmin->value)) {
            return true;
        }

        $hasSingle = isset($node['permission']);
        $hasAny = ($node['permissions'] ?? []) !== [];

        if ($hasSingle && ! $user->hasPermission($node['permission'])) {
            return false;
        }

        if ($hasAny) {
            foreach ((array) $node['permissions'] as $permission) {
                if ($user->hasPermission($permission)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
