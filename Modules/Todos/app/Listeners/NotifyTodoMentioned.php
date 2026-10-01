<?php

namespace Modules\Todos\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Jobs\SendTodoMentionJob;

/**
 * An @mention in a comment.
 */
class NotifyTodoMentioned implements ShouldQueue
{
    public function handle(TodoMentioned $event): void
    {
        SendTodoMentionJob::dispatch($event->comment, $event->mentionedUserIds, $event->actorId);
    }
}
