<?php

namespace Modules\Tasks\Http\Controllers;

use App\Enums\Priority;
use App\Enums\WorkItemStatus;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Tasks\Http\Requests\ReparentTaskRequest;
use Modules\Tasks\Models\Task;

/**
 * Sub-tasks — GAP-025.
 *
 * Every cycle check goes through `Task::wouldCreateCycle()`, the single
 * enforcement point. A cycle here is not a cosmetic problem: `descendants()`
 * walks the chain, so a circular parent makes task pages hang rather than merely
 * render oddly.
 */
class TaskSubtaskController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('createSubtask', $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'due_date' => ['nullable', 'date'],
            'responsible_user_id' => ['nullable', Rule::exists('users', 'id')],
        ]);

        $subtask = $task->subtasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? Priority::Medium->value,
            'status' => WorkItemStatus::Pending->value,
            'due_date' => $validated['due_date'] ?? null,
            'responsible_user_id' => $validated['responsible_user_id'] ?? $request->user()->id,
            'user_id' => $request->user()->id,
        ]);

        $this->activity->record(
            Task::class,
            $task,
            'subtask_created',
            null,
            ['subtask_id' => $subtask->id, 'title' => $subtask->title],
            $request->user()->id,
        );

        return back()->with('success', 'Sub-task created.');
    }

    /**
     * Move a task under a different parent, or detach it by sending null.
     */
    public function reparent(ReparentTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('reparent', $task);

        $parentId = $request->parentId();

        // The authoritative check. The request rule catches the trivial
        // self-parent case early, but only this one sees the whole chain.
        if ($task->wouldCreateCycle($parentId)) {
            return back()->withErrors([
                'parent_id' => 'That move would make the task its own ancestor.',
            ]);
        }

        $before = $task->parent_id;

        $task->forceFill(['parent_id' => $parentId])->save();

        $this->activity->record(
            Task::class,
            $task,
            'reparented',
            ['parent_id' => $before],
            ['parent_id' => $parentId],
            $request->user()->id,
        );

        return back()->with('success', $parentId === null ? 'Sub-task detached.' : 'Task moved.');
    }
}
