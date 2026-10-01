<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;
use Modules\Todos\Models\Todo;

/**
 * Notifies the To-Do's own party list about a new comment.
 *
 * Separate from the base job because the dedupe key must include the comment id:
 * without it, a second comment on the same To-Do would collide with the first
 * and never be delivered.
 */
class SendTodoCommentJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoCommented;
    }

    public function __construct(
        Todo $todo,
        ?int $actorId = null,
        string $discriminator = '',
    ) {
        parent::__construct($todo, $actorId, $discriminator);
    }
}
