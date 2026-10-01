<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\RecipientResolver;
use Modules\Todos\Services\TodoNotificationService;

/**
 * Notifies people named in an @mention.
 *
 * Separate from the ordinary comment notification because a mention is
 * escalated: the recipients are chosen by the caller rather than by the To-Do's
 * own party list, and the dedupe key is per mentioned user so two people being
 * mentioned in one comment produce two notifications rather than one.
 */
class SendTodoMentionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<int>  $mentionedUserIds
     */
    public function __construct(
        public Comment $comment,
        public array $mentionedUserIds = [],
        public ?int $actorId = null,
    ) {}

    public function handle(TodoNotificationService $notifications, RecipientResolver $recipients): void
    {
        $todo = Todo::query()->find($this->comment->commentable_id);

        if ($todo === null) {
            return;
        }

        $type = NotificationType::TodoMentioned;

        foreach ($recipients->users($this->mentionedUserIds, $this->actorId) as $recipient) {
            // The recipient id is part of the key: one mention, one notification,
            // and a re-fired comment pipeline cannot send the same mention twice.
            $notifications->deliver(
                $recipient,
                $todo,
                $type,
                (string) $recipient->id.':'.$this->comment->getKey(),
            );
        }
    }
}
