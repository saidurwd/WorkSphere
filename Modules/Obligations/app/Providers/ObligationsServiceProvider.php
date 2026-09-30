<?php

namespace Modules\Obligations\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Console\Scheduling\Schedule;

class ObligationsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Obligations';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'obligations';

    /**
     * Command classes to register.
     *
     * Laravel only auto-discovers app/Console/Commands, so module commands
     * have to be registered explicitly or they silently disappear.
     *
     * @var string[]
     */
    protected array $commands = [
        \Modules\Obligations\Console\Commands\ProcessObligationsCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    /**
     * Make this module's views resolvable by their existing relative names,
     * e.g. view('obligations.index'), in addition to the module namespace
     * (view('obligations::index')) that loadViewsFrom() registers.
     */
    public function boot(): void
    {
        // Parent boot registers commands, module views, config,
        // translations and migrations - all of which must still run.
        parent::boot();

        View::addLocation(module_path($this->name, 'resources/views'));
    }
}
