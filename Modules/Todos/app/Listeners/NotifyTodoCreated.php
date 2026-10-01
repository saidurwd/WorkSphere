<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoCreated;
use Modules\Todos\Jobs\SendTodoCreatedJob;

/**
 * A To-Do was created.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoCreated implements ShouldQueue
{
    public function handle(TodoCreated $event): void
    {
        SendTodoCreatedJob::dispatch($event->todo, $event->actorId);
    }
}
