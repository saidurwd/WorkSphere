<?php

namespace Modules\Todos\Http\Controllers;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Tag;
use App\Models\User;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Todos\Http\Requests\StoreTodoRequest;
use Modules\Todos\Http\Requests\UpdateTodoRequest;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoLinkService;
use Modules\Todos\Services\TodoReportService;
use Modules\Todos\Services\TodoService;

/**
 * To-Do list, detail and lifecycle.
 *
 * Two rules this controller is built around:
 *
 * 1. Every action calls `$this->authorize()`. `TodoScope` already narrows the
 *    list query, but a scope is a convenience, not a check — it can be removed by
 *    `withoutGlobalScope()` anywhere, so it must not be the only gate.
 * 2. Every mutating action delegates to TodoService rather than writing to the
 *    model directly, so the §3.2 transition graph and the activity trail cannot
 *    be bypassed by adding a controller method later.
 */
class TodoController extends Controller
{
    public function __construct(
        private readonly TodoService $todos,
        private readonly TodoLinkService $links,
        private readonly TodoReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $todos = $this->query($request)
            ->with(['assignee:id,name', 'creator:id,name'])
            ->withCount(['comments', 'checklistItems'])
            ->paginate(25)
            ->withQueryString();

        return view('todos.index', [
            'todos' => $todos,
            'filters' => $filters,
            'statuses' => StatusBadge::options(),
            'priorities' => Priority::cases(),
            'visibilities' => Visibility::cases(),
            'users' => $this->referenceUsers(),
            'summary' => $this->reports->summary(),
            'linkableTypes' => TodoLinkService::linkableTypes(),
        ]);
    }

    /**
     * §8.1 Inbox — `status = Inbox` with the same filters as the main list, so
     * the shortcut and the list can never drift apart.
     */
    public function inbox(Request $request): View
    {
        $request->merge(['status' => WorkItemStatus::Inbox->value]);

        $todos = $this->query($request)
            ->with(['assignee:id,name'])
            ->withCount(['comments', 'checklistItems'])
            ->paginate(25)
            ->withQueryString();

        return view('todos.index', [
            'todos' => $todos,
            'filters' => $this->filters($request) + ['status' => WorkItemStatus::Inbox->value],
            'statuses' => StatusBadge::options(),
            'priorities' => Priority::cases(),
            'visibilities' => Visibility::cases(),
            'users' => $this->referenceUsers(),
            'summary' => $this->reports->summary(),
            'linkableTypes' => TodoLinkService::linkableTypes(),
            'inboxOnly' => true,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Todo::class);

        return view('todos.create', [
            'todo' => new Todo(['status' => WorkItemStatus::Inbox, 'priority' => Priority::Medium, 'visibility' => Visibility::Personal]),
            'users' => $this->referenceUsers(),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
        ]);
    }

    public function store(StoreTodoRequest $request): RedirectResponse
    {
        $this->authorize('create', Todo::class);

        $attributes = $request->todoAttributes();

        // Assigning to somebody else at creation is a distinct permission from
        // being allowed to create a To-Do at all (§4).
        $assigneeId = $request->integer('assignee_id') ?: null;

        if ($assigneeId !== null) {
            $this->authorize('createForOthers', Todo::class);
        }

        $todo = $this->todos->create($request->user(), [
            ...$attributes,
            'assignee_id' => $assigneeId,
        ]);

        // Quick capture posts to the same endpoint and must stay on the list.
        if ($request->boolean('quick_capture')) {
            return back()->with('success', 'To-Do captured.');
        }

        return redirect()->route('todos.show', $todo)->with('success', 'To-Do created successfully.');
    }

    public function show(Todo $todo): View
    {
        $this->authorize('view', $todo);

        $todo->load([
            'assignee:id,name,email',
            'creator:id,name,email',
            'completedBy:id,name',
            'department:id,department_name',
            'watchers.user:id,name,email',
            'checklistItems',
            'links',
            'tags:id,name,slug,color',
            'comments.author:id,name',
        ]);

        $todo->loadCount(['comments', 'attachments']);

        // Reverse links, filtered so a link whose target the viewer cannot open
        // is never listed — the list itself would leak the target's existence.
        $reverseLinks = $this->links
            ->resolveReverse(request()->user(), 'todo', $todo->id)
            ->reject(fn (Todo $other): bool => $other->id === $todo->id)
            ->values();

        [$completed, $total, $percent] = $todo->checklistProgress();

        return view('todos.show', [
            'todo' => $todo,
            'linkedTargets' => $this->links->visibleTargets(request()->user(), $todo),
            'reverseLinks' => $reverseLinks,
            'activity' => ActivityLog::query()
                ->where('module_name', Todo::class)
                ->where('record_id', $todo->id)
                ->latest('id')
                ->paginate(10),
            'checklist' => ['completed' => $completed, 'total' => $total, 'percent' => $percent],
            'linkableTypes' => TodoLinkService::linkableTypes(),
            // Bounded: the watcher picker is a convenience, not a directory.
            'watchers' => $this->referenceUsers(),
        ]);
    }

    public function edit(Todo $todo): View
    {
        $this->authorize('update', $todo);

        return view('todos.edit', [
            'todo' => $todo,
            'users' => $this->referenceUsers(),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
        ]);
    }

    public function update(UpdateTodoRequest $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $this->todos->update($request->user(), $todo, $request->todoAttributes());

        return redirect()->route('todos.show', $todo)->with('success', 'To-Do updated successfully.');
    }

    /**
     * Lifecycle transitions. Each is a separate route so a link can be shared
     * and bookmarked for one specific action.
     */
    public function complete(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('complete', $todo);

        $this->todos->complete($request->user(), $todo);

        return back()->with('success', 'To-Do completed.');
    }

    public function reopen(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('reopen', $todo);

        $this->todos->reopen($request->user(), $todo);

        return back()->with('success', 'To-Do reopened.');
    }

    public function archive(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('archive', $todo);

        $this->todos->archive($request->user(), $todo);

        return back()->with('success', 'To-Do archived.');
    }

    public function restore(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('restore', $todo);

        $this->todos->restore($request->user(), $todo);

        return back()->with('success', 'To-Do restored.');
    }

    public function assign(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('assign', $todo);

        $assigneeId = $request->integer('assignee_id') ?: null;

        // The policy decides whether this actor may reassign; the existence of
        // the target user is a separate, server-side check that a forged id
        // cannot skip.
        abort_unless(
            $assigneeId === null || User::query()->whereKey($assigneeId)->exists(),
            422,
            'That user does not exist.',
        );

        $this->todos->assign($request->user(), $todo, $assigneeId);

        return back()->with('success', 'To-Do reassigned.');
    }

    public function transition(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(WorkItemStatus::class)],
            'waiting_on' => ['nullable', 'string', 'max:255'],
        ]);

        if ($todo->waiting_on !== $validated['waiting_on']) {
            $todo->update(['waiting_on' => $validated['waiting_on']]);
        }

        $this->todos->transition($request->user(), $todo, WorkItemStatus::from($validated['status']));

        return back()->with('success', 'Status updated.');
    }

    public function destroy(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('delete', $todo);

        $this->todos->destroy($request->user(), $todo);

        return redirect()->route('todos.index')->with('success', 'To-Do deleted.');
    }

    /**
     * Bulk actions. Authorised PER ITEM, not once for the batch: holding a
     * permission over one To-Do must not grant it over the other forty in the
     * selection. Unauthorised items are skipped and reported, never silently
     * applied.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:complete,reassign,archive,tag'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
            'assignee_id' => ['nullable', 'integer'],
            'tag' => ['nullable', 'string', 'max:100'],
        ]);

        $todos = Todo::query()->whereIn('id', $validated['ids'])->get();

        $applied = 0;
        $skipped = 0;

        foreach ($todos as $todo) {
            $ability = match ($validated['action']) {
                'complete' => 'complete',
                'reassign' => 'assign',
                'archive' => 'archive',
                'tag' => 'update',
            };

            if (Gate::forUser($request->user())->denies($ability, $todo)) {
                $skipped++;

                continue;
            }

            match ($validated['action']) {
                'complete' => $this->todos->complete($request->user(), $todo),
                'reassign' => $this->todos->assign($request->user(), $todo, $validated['assignee_id'] ?: null),
                'archive' => $this->todos->archive($request->user(), $todo),
                'tag' => $todo->tags()->syncWithoutDetaching([
                    Tag::findOrCreateByName($validated['tag'])->id,
                ]),
            };

            $applied++;
        }

        // The global scope means an id the actor may not act on is never even
        // selected, so it would never reach the loop and would go unreported. The
        // count has to be derived from the request, not from what we found.
        $skipped = max($skipped, count($validated['ids']) - $applied);

        $message = trans_choice('{1} :count To-Do updated.|[2,*] :count To-Dos updated.', $applied, ['count' => $applied]);

        if ($skipped > 0) {
            $message .= ' '.trans_choice('{1} :count skipped — not yours.|[2,*] :count skipped — not yours.', $skipped, ['count' => $skipped]);
        }

        return back()->with($applied > 0 ? 'success' : 'error', $message);
    }

    /**
     * The scoped, filtered query behind index and inbox.
     */
    protected function query(Request $request)
    {
        $filters = $this->filters($request);

        $query = Todo::query();

        if ($filters['search'] !== null) {
            $query->search($filters['search']);
        }

        if ($filters['status'] !== null) {
            $query->status($filters['status']);
        }

        if ($filters['priority'] !== null) {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['visibility'] !== null) {
            $query->where('visibility', $filters['visibility']);
        }

        if ($filters['assignee_id'] !== null) {
            $filters['assignee_id'] === 'none'
                ? $query->whereNull('assignee_id')
                : $query->where('assignee_id', $filters['assignee_id']);
        }

        return $query
            ->when($filters['overdue'] === true, fn ($q) => $q->overdue())
            ->when($filters['recurring'] === true, fn ($q) => $q->recurring())
            ->orderByRaw('due_date IS NULL, due_date')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? trim((string) $request->input('search')) : null,
            'status' => $request->filled('status') ? (string) $request->input('status') : null,
            'priority' => $request->filled('priority') ? (string) $request->input('priority') : null,
            'visibility' => $request->filled('visibility') ? (string) $request->input('visibility') : null,
            'assignee_id' => $request->filled('assignee_id') ? (string) $request->input('assignee_id') : null,
            'overdue' => $request->boolean('overdue'),
            'recurring' => $request->boolean('recurring'),
        ];
    }

    /**
     * Only the users a filter needs. `User::query()` unbounded would grow with
     * the company and the filter does not benefit from every account.
     *
     * @return Collection<int, User>
     */
    protected function referenceUsers()
    {
        return User::query()->orderBy('name')->limit(200)->get(['id', 'name']);
    }
}
