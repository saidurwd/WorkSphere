<?php

namespace Modules\Todos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Http\Requests\StoreTodoCommentRequest;
use Modules\Todos\Models\Todo;

/**
 * Comments on a To-Do.
 *
 * Writes to the shared `comments` table, not a To-Do-specific one — §4.5 exists
 * precisely so Meetings and To-Dos stop each having their own. A mention is
 * resolved from the body here rather than accepted as a submitted list, so a user
 * cannot silence a mention they never typed.
 */
class TodoCommentController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(StoreTodoCommentRequest $request, Todo $todo): RedirectResponse
    {
        $this->authorize('comment', $todo);

        $comment = $todo->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->integer('parent_id') ?: null,
            'body' => $request->validated('body'),
            'mentions' => $request->mentionedUserIds() ?: null,
        ]);

        $this->activity->record(
            Todo::class,
            $todo,
            'commented',
            null,
            ['comment_id' => $comment->id],
            $request->user()->id,
        );

        $mentioned = $request->mentionedUserIds();

        // Dispatched here, inside the request, so both the comment and its
        // notifications exist before the response is sent.
        TodoCommented::dispatch($comment, $request->user()->id);

        if ($mentioned !== []) {
            TodoMentioned::dispatch($comment, $mentioned, $request->user()->id);
        }

        return back()->with('success', 'Comment added.');
    }
}
