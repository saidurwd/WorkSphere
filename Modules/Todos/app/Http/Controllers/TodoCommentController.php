<?php

namespace Modules\Todos\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Todos\Http\Requests\StoreTodoCommentRequest;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoCommentService;

/**
 * Comments on a To-Do.
 *
 * Writes to the shared `comments` table, not a To-Do-specific one — §4.5 exists
 * precisely so Meetings and To-Dos stop each having their own. A mention is
 * resolved from the body by the request rather than accepted as a submitted list,
 * so a user cannot silence a mention they never typed.
 *
 * The write, its activity row and its notifications all live in
 * `TodoCommentService`, which the API controller uses too — two controllers
 * remembering the same three steps is how a comment ends up created without its
 * notification.
 */
class TodoCommentController extends Controller
{
    public function __construct(private readonly TodoCommentService $comments) {}

    public function store(StoreTodoCommentRequest $request, Todo $todo): RedirectResponse
    {
        $this->authorize('comment', $todo);

        $this->comments->store(
            $request->user(),
            $todo,
            $request->validated('body'),
            $request->integer('parent_id') ?: null,
            $request->mentionedUserIds(),
        );

        return back()->with('success', 'Comment added.');
    }
}
