<?php

namespace Modules\Todos\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Todos\Models\Todo;

/**
 * A To-Do changed hands. The previous assignee is told as a courtesy; the new
 * one is told by a separate TodoAssigned.
 */
class TodoReassigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Todo $todo,
        public int $previousAssigneeId,
        public ?int $actorId = null,
    ) {}
}
