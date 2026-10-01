<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Jobs\SendTodoCommentJob;
use Modules\Todos\Models\Todo;

/**
 * A comment was posted on a To-Do.
 *
 * Reaches the comment's subject rather than the event's payload, because
 * `comments` is the shared platform table and this listener is only the To-Do
 * entry point to it.
 */
class NotifyTodoCommented implements ShouldQueue
{
    public function handle(TodoCommented $event): void
    {
        $todo = Todo::query()->find($event->comment->commentable_id);

        if ($todo === null) {
            return;
        }

        SendTodoCommentJob::dispatch($todo, $event->actorId, (string) $event->comment->getKey());
    }
}
