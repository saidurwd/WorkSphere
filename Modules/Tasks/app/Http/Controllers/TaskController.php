<?php

namespace Modules\Tasks\Http\Controllers;

use App\Enums\WorkItemStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ResolvesReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Projects\Models\Project;
use Modules\Tasks\Events\TaskAssigned;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskRemarkSynchroniser;

class TaskController extends Controller
{
    use ResolvesReferenceData;

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Task::query()->with(['responsibleUser', 'project', 'taskTransfers'])->orderByDesc('due_date');

        // Must match TaskPolicy::view exactly, including watchers. Filtering the
        // list by a stricter rule than the policy would hide a task the user can
        // legitimately open; filtering it by a looser one would list tasks they
        // get 403 on.
        if (! $user->hasPermission('task.view')) {
            $query->forUser($user);
        }

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'priority' => $request->string('priority')->toString(),
            'due_date' => $request->string('due_date')->toString(),
            'responsible_user_id' => $request->integer('responsible_user_id', 0),
            'project_id' => $request->string('project_id')->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        })->when(
            WorkItemStatus::tryFrom($filters['status']) !== null,
            fn ($q) => $q->where('status', $filters['status'])
        )->when(in_array($filters['priority'], ['low', 'medium', 'high'], true), function ($q) use ($filters) {
            $q->where('priority', $filters['priority']);
        })->when($filters['responsible_user_id'] > 0, function ($q) use ($filters) {
            $q->where('responsible_user_id', $filters['responsible_user_id']);
        })->when($filters['project_id'] !== '', function ($q) use ($filters) {
            $q->where('project_id', $filters['project_id']);
        });

        if ($filters['due_date'] !== '') {
            $today = now()->startOfDay();
            $weekStart = $today->copy()->startOfWeek();
            $weekEnd = $today->copy()->endOfWeek();

            $query->when($filters['due_date'] === 'today', function ($q) use ($today) {
                $q->whereDate('due_date', $today);
            })->when($filters['due_date'] === 'this_week', function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('due_date', [$weekStart, $weekEnd]);
            })->when($filters['due_date'] === 'this_month', function ($q) use ($today) {
                $q->whereMonth('due_date', $today->month)
                    ->whereYear('due_date', $today->year);
            })->when($filters['due_date'] === 'future', function ($q) use ($today) {
                $q->whereDate('due_date', '>', $today);
            });
        }

        $tasks = $query->paginate(15)->withQueryString();
        $users = $this->referenceData()->users();
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('tasks.index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'users' => $users,
            'projects' => $projects,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Tasks'],
            ],
        ]);
    }

    public function dashboard(): View
    {
        $user = Auth::user();
        $tasksQuery = Task::query();

        if (! $user->hasPermission('task.view')) {
            $tasksQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id);
            });
        }

        $total = (clone $tasksQuery)->count();
        $completed = (clone $tasksQuery)->where('status', 'completed')->count();
        $pending = $total - $completed;

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $weekEnd = $today->copy()->endOfWeek();

        $todayTasks = (clone $tasksQuery)
            ->whereDate('due_date', $today)
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        $overdueTasks = (clone $tasksQuery)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', $today)
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        $completedTasks = (clone $tasksQuery)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->take(5)
            ->get();
        $upcomingTasks = (clone $tasksQuery)
            ->where('status', '!=', 'completed')
            ->whereBetween('due_date', [$tomorrow, $weekEnd])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        $highPriorityTasks = (clone $tasksQuery)
            ->where('priority', 'high')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        $mapTask = static function (Task $task): array {
            return [
                'title' => $task->title,
                'subtitle' => 'Due '.$task->due_date->format('M d, Y'),
                'url' => route('tasks.edit', $task),
                'badge' => [
                    'text' => ucfirst($task->priority),
                    'variant' => $task->priority === 'high'
                        ? 'danger'
                        : ($task->priority === 'medium' ? 'primary' : 'secondary'),
                ],
            ];
        };

        $statusCounts = [
            ['status' => 'pending', 'label' => 'Pending', 'count' => (clone $tasksQuery)->where('status', 'pending')->count()],
            ['status' => 'in_progress', 'label' => 'In Progress', 'count' => (clone $tasksQuery)->where('status', 'in_progress')->count()],
            ['status' => 'completed', 'label' => 'Completed', 'count' => $completed],
        ];
        $statusTotal = $statusCounts[0]['count'] + $statusCounts[1]['count'] + $statusCounts[2]['count'];
        $statusDonut = collect($statusCounts)->map(fn ($s) => [
            'label' => $s['label'],
            'count' => $s['count'],
            'pct' => $statusTotal > 0 ? (int) round($s['count'] / $statusTotal * 100) : 0,
            'color' => match ($s['status']) {
                'pending' => 'var(--bs-warning)',
                'in_progress' => 'var(--bs-info)',
                'completed' => 'var(--bs-success)',
            },
        ])->all();

        $weekStart = $today->copy()->startOfWeek();
        $weeklyBars = collect(range(0, 6))->map(function ($dayOffset) use ($tasksQuery, $weekStart) {
            $date = $weekStart->copy()->addDays($dayOffset);
            $count = (clone $tasksQuery)->whereDate('created_at', $date)->count();
            $maxCount = max((clone $tasksQuery)->count(), 1);

            return [
                'label' => $date->format('D'),
                'value' => $count,
                'pct' => (int) round($count / $maxCount * 100),
            ];
        })->all();

        $priorityCounts = [
            ['priority' => 'high', 'label' => 'High', 'count' => (clone $tasksQuery)->where('priority', 'high')->count()],
            ['priority' => 'medium', 'label' => 'Medium', 'count' => (clone $tasksQuery)->where('priority', 'medium')->count()],
            ['priority' => 'low', 'label' => 'Low', 'count' => (clone $tasksQuery)->where('priority', 'low')->count()],
        ];
        $priorityTotal = $priorityCounts[0]['count'] + $priorityCounts[1]['count'] + $priorityCounts[2]['count'];
        $priorityBars = collect($priorityCounts)->map(fn ($p) => [
            'label' => $p['label'],
            'value' => $p['count'],
            'pct' => $priorityTotal > 0 ? (int) round($p['count'] / $priorityTotal * 100) : 0,
            'color' => match ($p['priority']) {
                'high' => 'var(--bs-danger)',
                'medium' => 'var(--bs-info)',
                'low' => 'var(--bs-success)',
            },
        ])->all();

        $projectBars = Task::query()
            ->join('task_projects', 'tasks.project_id', '=', 'task_projects.id')
            ->selectRaw('task_projects.name, count(*) as total')
            ->groupBy('task_projects.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $projectMax = $projectBars->max('total') ?: 1;
        $projectBars = $projectBars->map(fn ($row) => [
            'label' => $row->name,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $projectMax * 100),
            'color' => 'var(--primary)',
        ])->all();

        return view('tasks.dashboard', [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'todayTasks' => $todayTasks->map($mapTask)->all(),
            'overdueTasks' => $overdueTasks->map($mapTask)->all(),
            'completedTasks' => $completedTasks->map($mapTask)->all(),
            'upcomingTasks' => $upcomingTasks->map($mapTask)->all(),
            'highPriorityTasks' => $highPriorityTasks->map($mapTask)->all(),
            'viewAllTodayRoute' => route('tasks.index', ['due_date' => 'today']),
            'viewAllOverdueRoute' => route('tasks.index'),
            'viewAllCompletedRoute' => route('tasks.index', ['status' => 'completed']),
            'viewAllUpcomingRoute' => route('tasks.index', ['due_date' => 'this_week']),
            'viewAllHighPriorityRoute' => route('tasks.index', ['priority' => 'high']),
            'statusDonut' => $statusDonut,
            'statusTotal' => $statusTotal,
            'weeklyBars' => $weeklyBars,
            'priorityBars' => $priorityBars,
            'projectBars' => $projectBars,
        ]);
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load([
            'user',
            'responsibleUser',
            'remarks' => function ($query) {
                $query->latest();
            },
            'remarks.user',
            'taskTransfers' => function ($query) {
                $query->latest('transfer_date');
            },
            'taskTransfers.fromUser',
            'taskTransfers.toUser',
            'taskTransfers.transferredBy',
            // GAP-025: sub-tasks and shared tags, both rendered on the detail page.
            'subtasks',
            'tags:id,name,slug,color',
        ]);

        return view('tasks.show', [
            'task' => $task,
            // GAP-025: the per-task timeline. Paged so a long history does not
            // load every entry into the detail page.
            'activityLog' => ActivityLog::query()
                ->where('module_name', Task::class)
                ->where('record_id', $task->id)
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Task::class);

        return view('tasks.create', [
            'users' => $this->referenceData()->users(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:pending,in_progress,completed'],
            'due_date' => ['required', 'date'],
            'responsible_user_id' => ['required', 'exists:users,id'],
            'project_id' => ['nullable', 'exists:task_projects,id'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $this->storeAttachment($request);
        }

        $task = Auth::user()->tasks()->create($validated);

        event(new TaskCreated($task));

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task,
            'users' => $this->referenceData()->users(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:pending,in_progress,completed'],
            'due_date' => ['required', 'date'],
            'responsible_user_id' => ['required', 'exists:users,id'],
            'project_id' => ['nullable', 'exists:task_projects,id'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($validated['status'] === 'completed' && ! $task->completed_at) {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $this->storeAttachment($request);
        }

        $oldStatus = $task->status;
        $oldResponsibleUserId = $task->responsible_user_id;

        $task->update($validated);

        if ($oldStatus !== 'completed' && $task->status === 'completed') {
            event(new TaskCompleted($task));
        } elseif ($oldResponsibleUserId != $task->responsible_user_id) {
            event(new TaskAssigned($task));
        } else {
            event(new TaskUpdated($task));
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function storeRemark(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('addRemark', $task);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'remark_attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('remark_attachment')) {
            $validated['attachment'] = $request->file('remark_attachment')->store('task_remarks', 'public');
        }

        $validated['user_id'] = Auth::id();

        $task->remarks()->create($validated);

        // GAP-048: dual-write into the shared comments table so one comment
        // stream can span Tasks, Meetings and To-Dos. The legacy row above is
        // untouched, so every existing screen and mail keeps working.
        app(TaskRemarkSynchroniser::class)
            ->mirror($task, $task->remarks()->latest('id')->firstOrFail());

        return back()->with('success', 'Remark added successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    private function storeAttachment(Request $request): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        return $request->file('attachment')->store('task-attachments', 'public');
    }
}
