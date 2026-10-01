<?php

namespace Modules\Todos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Todos\Models\Todo;

/**
 * The next occurrence of a recurring series was materialised.
 *
 * `$previousTodoId` is the occurrence that just completed, so the notification
 * can say "your next one is due" rather than repeating the same wording each
 * time in a series.
 */
class TodoRecurringGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
        public int $previousTodoId,
    ) {}
}
