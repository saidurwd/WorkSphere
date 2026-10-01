<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoAssigned;
use Modules\Todos\Jobs\SendTodoAssignedJob;

/**
 * A To-Do was assigned.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoAssigned implements ShouldQueue
{
    public function handle(TodoAssigned $event): void
    {
        SendTodoAssignedJob::dispatch($event->todo, $event->actorId);
    }
}
