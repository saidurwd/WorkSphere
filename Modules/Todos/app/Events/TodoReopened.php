<?php

namespace Modules\Todos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Todos\Models\Todo;

/**
 * A completed To-Do was reopened.
 *
 * Dispatched from TodoService, never from a controller, so no entry point can
 * change a To-Do without the corresponding notification pipeline running.
 */
class TodoReopened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
        public ?int $actorId = null,
    ) {}
}
