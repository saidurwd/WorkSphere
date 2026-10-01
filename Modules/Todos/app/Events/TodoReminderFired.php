<?php

namespace Modules\Todos\Events;

use App\Models\Reminder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A scheduled reminder came due. Fired by the `reminders:dispatch` command.
 */
class TodoReminderFired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Reminder $reminder,
        public int $todoId,
    ) {}
}
