<?php

namespace Modules\Todos\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Todos\Events\TodoAssigned;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoCompleted;
use Modules\Todos\Events\TodoCreated;
use Modules\Todos\Events\TodoDueSoon;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Events\TodoOverdue;
use Modules\Todos\Events\TodoReassigned;
use Modules\Todos\Events\TodoRecurringGenerated;
use Modules\Todos\Events\TodoReminderFired;
use Modules\Todos\Events\TodoReopened;
use Modules\Todos\Listeners\NotifyTodoAssigned;
use Modules\Todos\Listeners\NotifyTodoCommented;
use Modules\Todos\Listeners\NotifyTodoCompleted;
use Modules\Todos\Listeners\NotifyTodoCreated;
use Modules\Todos\Listeners\NotifyTodoDueSoon;
use Modules\Todos\Listeners\NotifyTodoMentioned;
use Modules\Todos\Listeners\NotifyTodoOverdue;
use Modules\Todos\Listeners\NotifyTodoReassigned;
use Modules\Todos\Listeners\NotifyTodoRecurringGenerated;
use Modules\Todos\Listeners\NotifyTodoReminderFired;
use Modules\Todos\Listeners\NotifyTodoReopened;

/**
 * Event → listener map for the To-Do module.
 *
 * Explicit rather than discovered: the existing Tasks module registers no event
 * provider at all, which is why its notifications only fire from the handful of
 * places that dispatch them explicitly. Discovering listeners by convention
 * silently drops any that live outside app/Listeners.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        TodoCreated::class => [NotifyTodoCreated::class],
        TodoAssigned::class => [NotifyTodoAssigned::class],
        TodoReassigned::class => [NotifyTodoReassigned::class],
        TodoCompleted::class => [NotifyTodoCompleted::class],
        TodoReopened::class => [NotifyTodoReopened::class],
        TodoRecurringGenerated::class => [NotifyTodoRecurringGenerated::class],
        TodoOverdue::class => [NotifyTodoOverdue::class],
        TodoDueSoon::class => [NotifyTodoDueSoon::class],
        TodoCommented::class => [NotifyTodoCommented::class],
        TodoMentioned::class => [NotifyTodoMentioned::class],
        TodoReminderFired::class => [NotifyTodoReminderFired::class],
    ];

    /**
     * Registration is handled by the parent, which reads `$listen` in its own
     * `register()`. Discovery is switched off because Laravel only auto-discovers
     * the application's `app/Listeners`, never a module's — with discovery on,
     * these eleven listeners would silently never fire.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
