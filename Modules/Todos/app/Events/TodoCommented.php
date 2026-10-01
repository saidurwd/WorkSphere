<?php

namespace Modules\Todos\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A comment was posted. `comment` is the shared platform table, not a To-Do
 * table, so the To-Do is reached through the comment's subject.
 */
class TodoCommented
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public ?int $actorId = null,
    ) {}
}
