<?php

namespace Modules\Todos\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * To-Do routes.
 *
 * Registered by hand rather than through nwidart's `RouteServiceProvider` base,
 * because that base wraps both web and API groups in `Route::middleware(...)
 * ->prefix(...)`, which produced the `/api/api/…` doubling recorded as GAP-017.
 * The module has no API surface yet, so only the web group is declared.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('')
            ->group(function (): void {
                $this->loadRoutesFrom(module_path('Todos', 'routes/web.php'));
            });
    }
}
