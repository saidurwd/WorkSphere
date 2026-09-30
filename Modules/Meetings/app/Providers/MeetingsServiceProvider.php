<?php

namespace Modules\Meetings\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Console\Scheduling\Schedule;

class MeetingsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Meetings';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'meetings';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    /**
     * Command classes to register.
     *
     * Laravel only auto-discovers app/Console/Commands, so module commands
     * must be registered explicitly or they silently disappear.
     *
     * @var string[]
     */
    protected array $commands = [
        \Modules\Meetings\Console\Commands\SendActionOverdueCommand::class,
        \Modules\Meetings\Console\Commands\SendActionRemindersCommand::class,
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
        View::addLocation(module_path($this->name, 'resources/views'));
    }
}
