<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Registers a module's `routes/api.php` under `/api/v1`.
 *
 * Every module needs this and nwidart's own `RouteServiceProvider` cannot provide
 * it correctly: its `mapApiRoutes()` applies `prefix('api')` and `name('api.')`,
 * which on a module whose routes are ALSO loaded by the framework's api group
 * produces `/api/api/…` (GAP-017). Whether that bites depends on how a given
 * module's provider happens to be wired, which is the worst possible property for
 * a routing convention to have.
 *
 * So the convention is declared once, here, and every module provider calls it.
 * Three properties it guarantees:
 *
 * - The version is in the URI, so `/api/v2` can exist beside `/api/v1` without
 *   breaking an integration.
 * - Module routes are NOT loaded inside the framework's api group, so there is no
 *   prefix to double up.
 * - Every route is `auth:sanctum` plus `throttle:api` by default; a mutating route
 *   adds `throttle:api-writes` at the route, not by convention, because "this one
 *   writes" is a fact about the route rather than about the module.
 */
trait RegistersApiRoutes
{
    /**
     * The URI prefix module API routes live under. The framework already applies
     * `api` to `routes/api.php`, so this is relative to that.
     */
    protected string $apiVersion = 'v1';

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/'.$this->apiVersion)
            ->name('api.'.$this->apiVersion.'.')
            ->middleware(['api', 'auth:sanctum', 'throttle:api'])
            ->group(module_path($this->name, '/routes/api.php'));
    }
}
