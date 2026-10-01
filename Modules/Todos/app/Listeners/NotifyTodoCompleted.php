<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoCompleted;
use Modules\Todos\Jobs\SendTodoCompletedJob;

/**
 * A To-Do was completed.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoCompleted implements ShouldQueue
{
    public function handle(TodoCompleted $event): void
    {
        SendTodoCompletedJob::dispatch($event->todo, $event->actorId);
    }
}
