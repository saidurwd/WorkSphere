<?php

namespace Modules\Meetings\Http\Controllers;

use App\Enums\WorkItemStatus;
use App\Http\Controllers\Controller;
use App\Support\ResolvesReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Meetings\Events\ActionItemAssigned;
use Modules\Meetings\Events\ActionItemCompleted;
use Modules\Meetings\Events\ActionItemCreated;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Tasks\Models\Task;

class MeetingActionItemController extends Controller
{
    use ResolvesReferenceData;

    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = MeetingActionItem::query()->with(['meeting', 'assignedTo', 'assignedDepartment', 'task']);

        if (! $user->hasPermission('meeting.view_all_actions')) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereHas('meeting', function ($q2) use ($user) {
                        $q2->where('organizer_id', $user->id)
                            ->orWhereHas('participants', function ($q3) use ($user) {
                                $q3->where('user_id', $user->id);
                            });
                    });
            });
        }

        $query->when($request->filled('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when($request->filled('assigned_to'), fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($request->filled('meeting_id'), fn ($q, $id) => $q->where('meeting_id', $id))
            ->when($request->filled('overdue'), fn ($q) => $q->overdue())
            ->when($request->filled('search'), function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });

        $actionItems = $query->orderByDesc('due_date')->paginate(15)->withQueryString();
        $meetings = Meeting::orderByDesc('meeting_date')->get(['id', 'title', 'meeting_no']);
        $users = $this->referenceData()->users();
        $departments = $this->referenceData()->departments();

        return view('meetings.action_items.index', compact('actionItems', 'meetings', 'users', 'departments'));
    }

    public function show(MeetingActionItem $actionItem): View
    {
        $this->authorize('view', $actionItem);

        $actionItem->load('meeting', 'assignedTo', 'assignedDepartment', 'task');

        return view('meetings.action_items.show', compact('actionItem'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        // `manageActionItems`, not `create_action`. `MeetingPolicy` has no
        // `create_action` method, so the Gate could not resolve the old ability and
        // every account without the `super-admin` role was refused — creating an
        // action item was impossible outside that role.
        $this->authorize('manageActionItems', $meeting);

        $validated = $request->validate([
            'action_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'assigned_department_id' => ['nullable', 'exists:departments,id'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:open,in_progress,completed,cancelled'],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['meeting_id'] = $meeting->id;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $actionItem = MeetingActionItem::create($validated);

        event(new ActionItemCreated($actionItem));

        return redirect()->route('meetings.show', $meeting)->with('success', 'Action item created successfully.');
    }

    public function update(Request $request, Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        $this->authorize('update', $actionItem);

        $validated = $request->validate([
            'action_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'assigned_department_id' => ['nullable', 'exists:departments,id'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:open,in_progress,completed,cancelled'],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $oldStatus = $actionItem->status;
        $oldAssignedTo = $actionItem->assigned_to;

        $validated['updated_by'] = auth()->id();

        $actionItem->update($validated);

        if ($oldStatus !== 'completed' && $validated['status'] === 'completed') {
            event(new ActionItemCompleted($actionItem->fresh()));
        }

        if ($oldAssignedTo != $validated['assigned_to'] && ! empty($validated['assigned_to'])) {
            event(new ActionItemAssigned($actionItem->fresh()));
        }

        return redirect()->route('meetings.show', $meeting)->with('success', 'Action item updated successfully.');
    }

    public function destroy(Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        $this->authorize('delete', $actionItem);

        $actionItem->delete();

        return redirect()->route('meetings.show', $meeting)->with('success', 'Action item deleted successfully.');
    }

    public function storeTask(Request $request, Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        $this->authorize('linkTask', $actionItem);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            // The task vocabulary, from the enum. This is a TASK being created from
            // an action item, so it takes task statuses — not the action item's own
            // `open|in_progress|completed|cancelled`, which the rules two methods
            // above use correctly.
            'status' => ['required', Rule::in(WorkItemStatus::taskValues())],
            'due_date' => ['nullable', 'date'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['user_id'] = auth()->id();
        $validated['task_no'] = 'TASK-'.str_pad((string) Task::count() + 1, 4, '0', STR_PAD_LEFT);

        $task = Task::create($validated);

        $actionItem->update(['task_id' => $task->id]);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Task created and linked to action item successfully.');
    }

    public function linkTask(Request $request, Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        $this->authorize('linkTask', $actionItem);

        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        $actionItem->update(['task_id' => $validated['task_id']]);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Task linked to action item successfully.');
    }

    public function unlinkTask(Meeting $meeting, MeetingActionItem $actionItem): RedirectResponse
    {
        $this->authorize('linkTask', $actionItem);

        $actionItem->update(['task_id' => null]);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Task unlinked from action item successfully.');
    }
}
