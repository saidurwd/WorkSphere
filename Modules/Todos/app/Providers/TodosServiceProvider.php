<?php

namespace Modules\Todos\Providers;

use Illuminate\Support\Facades\View;
use Modules\Todos\Console\Commands\GenerateTodoOccurrencesCommand;
use Modules\Todos\Console\Commands\SendTodoDueSoonCommand;
use Modules\Todos\Console\Commands\SendTodoOverdueCommand;
use Modules\Todos\Console\Commands\SkipTodoOccurrenceCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TodosServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Todos';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'todos';

    /**
     * Command classes to register.
     *
     * Deliberately empty for now: `todos:overdue`, `todos:due-soon`,
     * `todos:generate`, `todos:skip` and the shared `reminders:dispatch` are
     * Phase 6 work, together with the scheduler entries that call them.
     * Registering classes that do not exist yet would fatal the provider.
     *
     * @var array<int, class-string>
     */
    protected array $commands = [
        SendTodoOverdueCommand::class,
        SendTodoDueSoonCommand::class,
        GenerateTodoOccurrencesCommand::class,
        SkipTodoOccurrenceCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var array<int, class-string>
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Make this module's views resolvable by their existing relative names,
     * e.g. view('todos.index'), in addition to the module namespace.
     */
    public function boot(): void
    {
        parent::boot();

        View::addLocation(module_path($this->name, 'resources/views'));
    }
}
