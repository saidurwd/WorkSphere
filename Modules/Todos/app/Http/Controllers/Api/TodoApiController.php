<?php

namespace Modules\Todos\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TodoResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Todos\Http\Requests\AssignTodoRequest;
use Modules\Todos\Http\Requests\CompleteTodoRequest;
use Modules\Todos\Http\Requests\IndexTodoRequest;
use Modules\Todos\Http\Requests\StoreTodoCommentRequest;
use Modules\Todos\Http\Requests\StoreTodoRequest;
use Modules\Todos\Http\Requests\UpdateTodoRequest;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoCommentService;
use Modules\Todos\Services\TodoService;

/**
 * `/api/v1/todos` — TODO-MODULE-SPECIFICATION.md §9.1.
 *
 * Every mutating endpoint delegates to `TodoService` and authorises through
 * `TodoPolicy`, exactly as `TodoController` does. The service, the requests and
 * the policy are the same objects the web layer uses, which is the whole point:
 * a second implementation of the transition graph or of the assignment rules
 * would be a second set of bugs, and the two would disagree within one release.
 *
 * The visible query is NOT `Todo::query()` alone — `TodoScope` already narrows it,
 * and that is what makes "an API user cannot see a record the web UI would hide"
 * true by construction rather than by a second filter somebody has to remember.
 */
class TodoApiController extends ApiController
{
    /**
     * The activity trail is bounded. A To-Do with a thousand writes would
     * otherwise return a thousand rows to a client that asked for "the log", and
     * the web page paginates it at ten for good reason.
     */
    private const ACTIVITY_LIMIT = 50;

    public function __construct(
        private readonly TodoService $todos,
        private readonly TodoCommentService $commentService,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(IndexTodoRequest $request): AnonymousResourceCollection
    {
        return $this->paginated(
            $this->query($request),
            TodoResource::class,
            $request,
        );
    }

    public function show(Request $request, Todo $todo): TodoResource
    {
        $this->authorizeAction($this->actor($request), 'view', $todo);

        return new TodoResource(
            $todo->load([
                'assignee', 'creator',
                'checklistItems', 'tags', 'links',
            ])->loadCount(['comments', 'checklistItems']),
        );
    }

    public function store(StoreTodoRequest $request): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'create', Todo::class);

        $assigneeId = $request->integer('assignee_id') ?: null;

        // Same rule as the web: delegating to somebody else at creation is a
        // distinct permission from creating a To-Do at all (§4).
        if ($assigneeId !== null) {
            $this->authorizeAction($user, 'createForOthers', Todo::class);
        }

        $todo = $this->todos->create($user, [
            ...$request->todoAttributes(),
            'assignee_id' => $assigneeId,
        ]);

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    public function update(UpdateTodoRequest $request, Todo $todo): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'update', $todo);

        $this->todos->update($user, $todo, $request->todoAttributes());

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    public function destroy(Request $request, Todo $todo): Response
    {
        $this->authorizeAction($this->actor($request), 'delete', $todo);

        $this->todos->destroy($this->actor($request), $todo);

        return response()->noContent();
    }

    public function complete(CompleteTodoRequest $request, Todo $todo): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'complete', $todo);

        $this->todos->complete($user, $todo);

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    public function reopen(Request $request, Todo $todo): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'reopen', $todo);

        $this->todos->reopen($user, $todo);

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    public function archive(Request $request, Todo $todo): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'archive', $todo);

        $this->todos->archive($user, $todo);

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    public function restore(Request $request, Todo $todo): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'restore', $todo);

        $this->todos->restore($user, $todo);

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    public function assign(AssignTodoRequest $request, Todo $todo): TodoResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'assign', $todo);

        $this->todos->assign($user, $todo, $request->assigneeId());

        return new TodoResource($todo->refresh()->load(['assignee', 'creator']));
    }

    /**
     * Comments on a To-Do, top-level first.
     *
     * Replies are nested under their parent rather than returned as a flat list,
     * because a client that renders the thread needs the tree and one that does
     * not can ignore it — whereas a flat list forces every client to rebuild the
     * hierarchy from `parent_id`.
     */
    public function comments(Request $request, Todo $todo): AnonymousResourceCollection
    {
        $this->authorizeAction($this->actor($request), 'view', $todo);

        return CommentResource::collection($this->commentService->thread($todo));
    }

    public function storeComment(StoreTodoCommentRequest $request, Todo $todo): CommentResource
    {
        $user = $this->actor($request);

        $this->authorizeAction($user, 'comment', $todo);

        $comment = $this->commentService->store(
            $user,
            $todo,
            $request->validated('body'),
            $request->integer('parent_id') ?: null,
            $request->mentionedUserIds(),
        );

        return new CommentResource($comment->load('author'));
    }

    public function activity(Request $request, Todo $todo): JsonResponse
    {
        $this->authorizeAction($this->actor($request), 'view', $todo);

        // `subject_type`/`subject_id`, not `module_name`/`record_id`: the polymorphic
        // columns are what every consumer should read from, and `module_name` is a
        // class BASENAME so it cannot distinguish two models that share one.
        $logs = ActivityLog::query()
            ->where('subject_type', Todo::class)
            ->where('subject_id', $todo->id)
            ->latest('id')
            ->limit(self::ACTIVITY_LIMIT)
            ->get();

        return response()->json([
            'data' => $logs->map(fn (ActivityLog $log): array => [
                'id' => (int) $log->id,
                'action' => (string) $log->action,
                'actor_id' => $log->user_id,
                'old_value' => $log->old_value,
                'new_value' => $log->new_value,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * The filtered, scoped list query.
     *
     * @return Builder<Todo>
     */
    protected function query(IndexTodoRequest $request): Builder
    {
        $query = Todo::query()
            ->with(['assignee', 'creator'])
            ->withCount(['comments', 'checklistItems']);

        if ($request->filled('q')) {
            $query->search($request->string('q')->trim()->toString());
        }

        if ($request->filled('status')) {
            $query->status($request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->string('visibility')->toString());
        }

        if ($request->filled('assignee_id')) {
            $value = $request->string('assignee_id')->toString();

            $value === 'none'
                ? $query->whereNull('assignee_id')
                : $query->where('assignee_id', (int) $value);
        }

        if ($request->filled('creator_id')) {
            $query->where('creator_id', $request->integer('creator_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('due_before')) {
            $query->whereDate('due_date', '<=', $request->date('due_before'));
        }

        if ($request->filled('due_after')) {
            $query->whereDate('due_date', '>=', $request->date('due_after'));
        }

        if ($request->filled('tag')) {
            $slug = $request->string('tag')->trim()->toString();

            $query->whereHas('tags', fn (Builder $tags) => $tags->where('tags.slug', $slug));
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        if ($request->boolean('recurring')) {
            $query->recurring();
        }

        return $query;
    }
}
