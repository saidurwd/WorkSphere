<?php

namespace Modules\Todos\Http\Controllers;

use App\Enums\LinkType;
use App\Enums\Priority;
use App\Enums\WorkItemStatus;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoLinkService;
use Modules\Todos\Services\TodoService;

/**
 * One-click To-Do creation from another module — §7.2.
 *
 * Everything funnels through `TodoService::create()` so a To-Do made from an
 * action item is indistinguishable from one made in the To-Do module: same state
 * machine, same activity trail, same assignment notification.
 *
 * The reverse direction already exists — `TodoLinkService::resolveReverse()`
 * answers "which To-Dos point at this record" — so both halves of the navigation
 * are covered by the same link row rather than a second nullable FK column.
 */
class CrossModuleLinkController extends Controller
{
    public function __construct(
        private readonly TodoLinkService $links,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Create a To-Do from a meeting action item and link the two.
     */
    public function storeFromActionItem(Request $request, MeetingActionItem $actionItem): RedirectResponse
    {
        $this->authorize('createForOthers', Todo::class);

        $todo = app(TodoService::class)->create($request->user(), [
            'title' => $this->titleFor($actionItem->title, $request->input('title')),
            'description' => $this->descriptionFromActionItem($actionItem),
            'priority' => $this->priorityFor($actionItem->priority),
            'status' => WorkItemStatus::InProgress,
            'assignee_id' => $actionItem->assigned_to,
        ]);

        $this->links->attach($request->user(), $todo, 'meeting_action_item', $actionItem->id, LinkType::DerivedFrom);

        $this->activity->record(
            MeetingActionItem::class,
            $actionItem,
            'todo_created',
            null,
            ['todo_id' => $todo->id],
            $request->user()->id,
        );

        return back()->with('success', 'To-Do created from the action item.');
    }

    /**
     * Create a To-Do from an obligation and link the two.
     */
    public function storeFromObligation(Request $request, Obligation $obligation): RedirectResponse
    {
        $this->authorize('createForOthers', Todo::class);

        $todo = app(TodoService::class)->create($request->user(), [
            'title' => $this->titleFor($obligation->title, $request->input('title')),
            'description' => $this->descriptionFromObligation($obligation),
            'priority' => $obligation->priority ?? Priority::Medium,
            'status' => WorkItemStatus::InProgress,
            'assignee_id' => $obligation->owner_user_id,
            'department_id' => $obligation->department_id,
        ]);

        $this->links->attach($request->user(), $todo, 'obligation', $obligation->id, LinkType::DerivedFrom);

        $this->activity->record(
            Obligation::class,
            $obligation,
            'todo_created',
            null,
            ['todo_id' => $todo->id],
            $request->user()->id,
        );

        return back()->with('success', 'To-Do created from the obligation.');
    }

    /**
     * Create a To-Do from a task and link the two.
     */
    public function storeFromTask(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('createForOthers', Todo::class);

        $todo = app(TodoService::class)->create($request->user(), [
            'title' => $this->titleFor($task->title, $request->input('title')),
            'description' => $task->description,
            'priority' => $task->priority ?? Priority::Medium,
            'status' => WorkItemStatus::InProgress,
            'assignee_id' => $task->responsible_user_id,
        ]);

        $this->links->attach($request->user(), $todo, 'task', $task->id, LinkType::DerivedFrom);

        $this->activity->record(
            Task::class,
            $task,
            'todo_created',
            null,
            ['todo_id' => $todo->id],
            $request->user()->id,
        );

        return back()->with('success', 'To-Do created from the task.');
    }

    /**
     * The To-Dos that point at a record — the reverse half of the navigation.
     */
    public function reverse(Request $request, string $morphKey, int $id)
    {
        $this->authorize('createForOthers', Todo::class);

        return response()->json([
            'data' => $this->links
                ->resolveReverse($request->user(), $morphKey, $id)
                ->map(fn (Todo $todo): array => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'url' => route('todos.show', $todo),
                ])
                ->all(),
        ]);
    }

    protected function titleFor(string $sourceTitle, ?string $override): string
    {
        $title = $override !== null && trim($override) !== '' ? trim($override) : $sourceTitle;

        return mb_substr($title, 0, 255);
    }

    protected function descriptionFromActionItem(MeetingActionItem $actionItem): string
    {
        return sprintf('Created from meeting action item #%d.', $actionItem->id);
    }

    protected function descriptionFromObligation(Obligation $obligation): string
    {
        return sprintf('Created from obligation #%d.', $obligation->id);
    }

    /**
     * Action items carry `normal|important|urgent|high|...`; To-Dos carry the
     * shared Priority vocabulary. `important` has no Priority case, so it maps to
     * `high` rather than being dropped.
     */
    protected function priorityFor(?string $priority): Priority
    {
        $mapped = match ($priority) {
            'important', 'urgent' => Priority::High->value,
            default => $priority,
        };

        return Priority::tryFrom((string) $mapped) ?? Priority::Medium;
    }
}
