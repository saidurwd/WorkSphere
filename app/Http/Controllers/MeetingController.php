<?php

namespace App\Http\Controllers;

use App\Events\MeetingCancelled;
use App\Events\MeetingCompleted;
use App\Events\MeetingCreated;
use App\Events\MeetingStarted;
use App\Events\MeetingUpdated;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\Task;
use App\Models\User;
use App\Services\MeetingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function __construct(private MeetingService $meetingService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Meeting::query()->with(['type', 'organizer', 'department']);

        if (! $this->canManageAll($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhereHas('participants', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            });
        }

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'meeting_type_id' => $request->integer('meeting_type_id', 0),
            'department_id' => $request->integer('department_id', 0),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('meeting_no', 'like', $search);
            });
        })->when($filters['status'] !== '', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when($filters['meeting_type_id'] > 0, function ($q) use ($filters) {
            $q->where('meeting_type_id', $filters['meeting_type_id']);
        })->when($filters['department_id'] > 0, function ($q) use ($filters) {
            $q->where('department_id', $filters['department_id']);
        })->when($filters['date_from'] !== '', function ($q) use ($filters) {
            $q->whereDate('meeting_date', '>=', $filters['date_from']);
        })->when($filters['date_to'] !== '', function ($q) use ($filters) {
            $q->whereDate('meeting_date', '<=', $filters['date_to']);
        });

        $meetings = $query->orderByDesc('meeting_date')->paginate(15)->withQueryString();
        $types = MeetingType::orderBy('sort_order')->get();
        $departments = Department::orderBy('department_name')->get();

        return view('meetings.index', compact('meetings', 'types', 'departments', 'filters'));
    }

    public function create(): View
    {
        $types = MeetingType::orderBy('sort_order')->get();
        $users = User::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('department_name')->get();

        return view('meetings.create', compact('types', 'users', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meeting_type_id' => ['required', 'exists:meeting_types,id'],
            'organizer_id' => ['required', 'exists:users,id'],
            'chairperson_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'description' => ['nullable', 'string'],
            'agenda' => ['nullable', 'string'],
            'participants' => ['nullable', 'array'],
            'participants.*.user_id' => ['required_with:participants', 'exists:users,id'],
            'participants.*.participant_type' => ['required_with:participants', 'in:organizer,chairperson,member,guest,presenter,observer'],
            'participants.*.attendance_status' => ['required_with:participants', 'in:invited,accepted,declined,present,absent,apology'],
        ]);

        $meeting = $this->meetingService->create($validated);

        event(new MeetingCreated($meeting));

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting created successfully.');
    }

    public function show(Meeting $meeting): View
    {
        $meeting->load([
            'type',
            'organizer',
            'chairperson',
            'department',
            'participants.user',
            'agendas.discussions',
            'agendas.discussions.decisions',
            'decisions',
            'actionItems.assignedTo',
            'actionItems.assignedDepartment',
            'actionItems.task',
            'attachments',
            'versions',
            'tags',
            'recurrence',
            'minutesApprovals.approver',
        ]);

        $users = User::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('department_name')->get();
        $tasks = Task::orderByDesc('created_at')->get(['id', 'task_no', 'title', 'status', 'priority']);

        return view('meetings.show', compact('meeting', 'users', 'departments', 'tasks'));
    }

    public function print(Meeting $meeting): View
    {
        $meeting->load([
            'type',
            'organizer',
            'chairperson',
            'department',
            'participants.user',
            'agendas.discussions',
            'agendas.discussions.decisions',
            'decisions',
            'actionItems.assignedTo',
            'actionItems.assignedDepartment',
            'actionItems.task',
            'attachments',
            'versions',
            'tags',
            'recurrence',
            'minutesApprovals.approver',
        ]);

        return view('meetings.print', compact('meeting'));
    }

    public function edit(Meeting $meeting): View
    {
        $types = MeetingType::orderBy('sort_order')->get();
        $users = User::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('department_name')->get();

        return view('meetings.edit', compact('meeting', 'types', 'users', 'departments'));
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meeting_type_id' => ['required', 'exists:meeting_types,id'],
            'organizer_id' => ['required', 'exists:users,id'],
            'chairperson_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'description' => ['nullable', 'string'],
            'agenda' => ['nullable', 'string'],
        ]);

        $this->meetingService->update($meeting, $validated);

        event(new MeetingUpdated($meeting));

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting updated successfully.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        if (in_array($meeting->status, ['completed', 'approved'], true)) {
            return back()->with('error', 'Completed or approved meetings cannot be deleted.');
        }

        $meeting->delete();

        return redirect()->route('meetings.index')->with('success', 'Meeting deleted successfully.');
    }

    public function start(Meeting $meeting): RedirectResponse
    {
        $this->meetingService->start($meeting);

        event(new MeetingStarted($meeting->fresh()));

        return back()->with('success', 'Meeting started.');
    }

    public function complete(Meeting $meeting): RedirectResponse
    {
        $this->meetingService->complete($meeting);

        event(new MeetingCompleted($meeting->fresh()));

        return back()->with('success', 'Meeting completed.');
    }

    public function cancel(Meeting $meeting): RedirectResponse
    {
        $this->meetingService->cancel($meeting);

        event(new MeetingCancelled($meeting->fresh()));

        return back()->with('success', 'Meeting cancelled.');
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
