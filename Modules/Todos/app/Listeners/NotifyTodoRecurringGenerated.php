<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoRecurringGenerated;
use Modules\Todos\Jobs\SendTodoRecurringJob;

/**
 * The next occurrence was generated.
 *
 * ShouldQueue: the listener itself runs off the request. It does nothing but
 * push the job — no delivery, no database writes, no mail (GAP-022).
 */
class NotifyTodoRecurringGenerated implements ShouldQueue
{
    public function handle(TodoRecurringGenerated $event): void
    {
        SendTodoRecurringJob::dispatch($event->todo, $event->actorId);
    }
}
