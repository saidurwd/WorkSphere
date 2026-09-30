<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Application-wide event wiring.
 *
 * Every domain event now lives with the module that owns it, and each module
 * registers its own listeners through its EventServiceProvider:
 *
 *   Modules\Meetings\Providers\EventServiceProvider
 *   Modules\Tasks\Providers\EventServiceProvider
 *
 * This provider is kept for the framework's email-verification hooks and as
 * the place to register genuinely cross-cutting application events.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];

    /**
     * Domain modules declare their own explicit mappings, so the application
     * provider does not need to discover anything.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
