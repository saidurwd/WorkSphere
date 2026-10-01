<?php

namespace Modules\Todos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Todos\Models\Todo;

/**
 * A To-Do is due shortly.
 *
 * Dispatched from TodoService, never from a controller, so no entry point can
 * change a To-Do without the corresponding notification pipeline running.
 */
class TodoDueSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
        public ?int $actorId = null,
    ) {}
}
