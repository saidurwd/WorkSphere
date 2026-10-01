<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoReassigned;
use Modules\Todos\Jobs\SendTodoReassignedJob;

/**
 * A To-Do changed hands.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoReassigned implements ShouldQueue
{
    public function handle(TodoReassigned $event): void
    {
        SendTodoReassignedJob::dispatch($event->todo, $event->actorId);
    }
}
