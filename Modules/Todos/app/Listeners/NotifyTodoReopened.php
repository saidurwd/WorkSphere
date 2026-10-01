<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoReopened;
use Modules\Todos\Jobs\SendTodoReopenedJob;

/**
 * A To-Do was reopened.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoReopened implements ShouldQueue
{
    public function handle(TodoReopened $event): void
    {
        SendTodoReopenedJob::dispatch($event->todo, $event->actorId);
    }
}
