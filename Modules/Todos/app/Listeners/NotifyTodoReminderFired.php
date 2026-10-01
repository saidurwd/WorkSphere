<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoReminderFired;
use Modules\Todos\Jobs\SendTodoReminderJob;
use Modules\Todos\Models\Todo;

/**
 * A scheduled reminder came due.
 *
 * The discriminator is the fire date, so a reminder for the same To-Do on two
 * different days produces two notifications rather than one — which is what a
 * user who asked for a daily reminder expects.
 */
class NotifyTodoReminderFired implements ShouldQueue
{
    public function handle(TodoReminderFired $event): void
    {
        $todo = Todo::query()->find($event->todoId);

        if ($todo === null) {
            return;
        }

        SendTodoReminderJob::dispatch(
            $todo,
            null,
            $event->reminder->remind_at->toDateString(),
        );
    }
}
