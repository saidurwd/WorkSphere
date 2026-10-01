<?php

namespace Modules\Tasks\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TaskResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tasks\Http\Requests\IndexTaskRequest;
use Modules\Tasks\Models\Task;

/**
 * `/api/v1/tasks` — read only.
 *
 * Task writes are deliberately absent from v1. The web layer writes tasks through
 * `TaskController` and a `TaskService` that carry transfer, sub-task, time-entry
 * and watcher side effects; an API that reimplemented those would be a second set
 * of rules with a different set of bugs. Read endpoints are additive and safe to
 * expose now; the write surface lands when there is one service to share.
 *
 * Visibility mirrors `TaskController::index` exactly: the same `task.view`
 * permission gates the same `forUser` narrowing, watchers included. That is the
 * acceptance criterion "an API user cannot see a record the web UI would hide",
 * and it holds because both call the same scope.
 */
class TaskApiController extends ApiController
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $user = $this->actor($request);

        $query = Task::query()
            ->with(['user', 'responsibleUser', 'project', 'tags'])
            ->withCount(['subtasks', 'sharedComments']);

        if (! $this->allows($user, 'task.view')) {
            $query->forUser($user);
        }

        return $this->paginated($this->filter($query, $request), TaskResource::class, $request);
    }

    public function show(Request $request, Task $task): TaskResource
    {
        $this->authorizeAction($this->actor($request), 'view', $task);

        return new TaskResource(
            $task->load(['user', 'responsibleUser', 'project', 'tags'])
                ->loadCount(['subtasks', 'sharedComments']),
        );
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    protected function filter(Builder $query, IndexTaskRequest $request): Builder
    {
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim()->toString().'%';

            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->status($request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        foreach (['project_id', 'responsible_user_id', 'user_id', 'parent_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->integer($column));
            }
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

        if ($request->boolean('top_level')) {
            $query->topLevel();
        }

        return $query;
    }
}
