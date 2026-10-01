<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoDueSoon;
use Modules\Todos\Jobs\SendTodoDueSoonJob;

/**
 * A To-Do is due shortly.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoDueSoon implements ShouldQueue
{
    public function handle(TodoDueSoon $event): void
    {
        SendTodoDueSoonJob::dispatch($event->todo, $event->actorId);
    }
}
