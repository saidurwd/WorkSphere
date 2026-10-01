<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoOverdue;
use Modules\Todos\Jobs\SendTodoOverdueJob;

/**
 * A To-Do went overdue.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoOverdue implements ShouldQueue
{
    public function handle(TodoOverdue $event): void
    {
        SendTodoOverdueJob::dispatch($event->todo, $event->actorId);
    }
}
