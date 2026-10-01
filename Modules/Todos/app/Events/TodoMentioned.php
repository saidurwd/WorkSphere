<?php

namespace Modules\Todos\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An @mention in a comment. Split from TodoCommented so a mention can be
 * escalated to email while an ordinary comment stays in-app.
 */
class TodoMentioned
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $mentionedUserIds
     */
    public function __construct(
        public Comment $comment,
        public array $mentionedUserIds = [],
        public ?int $actorId = null,
    ) {}
}
