<?php

namespace Modules\Todos\Services;

use App\Models\Comment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Models\Todo;

/**
 * Writes a comment on a To-Do.
 *
 * This exists because two controllers write the same comment and both had to
 * remember three things: create the row, record the activity entry, and dispatch
 * `TodoCommented` / `TodoMentioned`. Duplicated across a web controller and an API
 * controller, that is a comment that gets created without its notification, or a
 * notification dispatched before the comment exists to be read.
 *
 * The events are dispatched from here, inside the transaction, so a listener that
 * loads the comment cannot race its own creation — and so the phase-4 rule that
 * events originate in the service rather than the controller is actually true
 * rather than only enforced for the routes it happened to be written for.
 *
 * Mentions are passed in, already resolved from the body by
 * `StoreTodoCommentRequest::mentionedUserIds()`. Accepting a submitted list
 * instead would let a client post a comment that names nobody.
 */
class TodoCommentService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @param  list<int>  $mentionedUserIds
     */
    public function store(User $actor, Todo $todo, string $body, ?int $parentId, array $mentionedUserIds): Comment
    {
        return DB::transaction(function () use ($actor, $todo, $body, $parentId, $mentionedUserIds): Comment {
            $comment = $todo->comments()->create([
                'user_id' => $actor->id,
                'parent_id' => $parentId,
                'body' => $body,
                'mentions' => $mentionedUserIds ?: null,
            ]);

            $this->activity->record(
                Todo::class,
                $todo,
                'commented',
                null,
                ['comment_id' => $comment->id],
                $actor->id,
            );

            TodoCommented::dispatch($comment, $actor->id);

            if ($mentionedUserIds !== []) {
                TodoMentioned::dispatch($comment, $mentionedUserIds, $actor->id);
            }

            return $comment;
        });
    }

    /**
     * @return Collection<int, Comment>
     */
    public function thread(Todo $todo): Collection
    {
        return $todo->comments()
            ->with('author')
            ->whereNull('parent_id')
            ->with(['replies' => fn ($query) => $query->with('author')->oldest()])
            ->oldest()
            ->get();
    }
}
