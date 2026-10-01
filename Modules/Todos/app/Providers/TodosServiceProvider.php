<?php

namespace Modules\Todos\Providers;

use Illuminate\Support\Facades\View;
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
     * Make this module's views resolvable by their existing relative names,
     * e.g. view('todos.index'), in addition to the module namespace.
     */
    public function boot(): void
    {
        parent::boot();

        View::addLocation(module_path($this->name, 'resources/views'));
    }
}
