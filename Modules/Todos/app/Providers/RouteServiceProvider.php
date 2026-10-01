<?php

namespace Modules\Todos\Providers;

use App\Support\RegistersApiRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * To-Do routes.
 *
 * Registered by hand rather than through nwidart's `RouteServiceProvider` base,
 * because that base wraps both web and API groups in `Route::middleware(...)
 * ->prefix(...)`, which produced the `/api/api/…` doubling recorded as GAP-017.
 *
 * Both groups are declared here and both apply the versioned API convention
 * explicitly, so the module's API surface appears at `/api/v1/todos` rather than
 * at a path that depends on how the module provider happens to be wired.
 */
class RouteServiceProvider extends ServiceProvider
{
    use RegistersApiRoutes;

    protected string $name = 'Todos';

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('')
            ->group(function (): void {
                $this->loadRoutesFrom(module_path('Todos', 'routes/web.php'));
            });

        $this->mapApiRoutes();
    }
}
