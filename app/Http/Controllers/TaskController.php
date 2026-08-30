<?php

namespace App\Http\Controllers;

use App\Mail\TaskAssigned;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Task::query()->with(['responsibleUser', 'project', 'taskTransfers'])->orderByDesc('due_date');

        if (! $this->canManageAllTasks($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id);
            });
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
        })->when(in_array($filters['status'], ['pending', 'in_progress', 'completed'], true), function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when(in_array($filters['priority'], ['low', 'medium', 'high'], true), function ($q) use ($filters) {
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
        $users = User::orderBy('name')->get(['id', 'name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('tasks.index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'users' => $users,
            'projects' => $projects,
        ]);
    }

    public function dashboard(): View
    {
        $user = Auth::user();
        $tasksQuery = Task::query();

        if (! $this->canManageAllTasks($user)) {
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
                    'class' => $task->priority === 'high'
                        ? 'badge-danger'
                        : ($task->priority === 'medium' ? 'badge-primary' : 'badge-secondary'),
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
                'pending' => 'var(--warning)',
                'in_progress' => 'var(--info)',
                'completed' => 'var(--success)',
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
                'high' => 'var(--destructive)',
                'medium' => 'var(--info)',
                'low' => 'var(--success)',
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
        $this->authorizeTask($task);

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
        ]);

        return view('tasks.show', [
            'task' => $task,
        ]);
    }

    public function create(): View
    {
        return view('tasks.create', [
            'users' => User::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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

        $this->notifyAssignee($task, $task->responsibleUser, Auth::user());

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        $this->authorizeTask($task);

        return view('tasks.edit', [
            'task' => $task,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

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

        $task->update($validated);

        $this->notifyAssignee($task, $task->responsibleUser, Auth::user());

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function storeRemark(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'remark_attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('remark_attachment')) {
            $validated['attachment'] = $request->file('remark_attachment')->store('task_remarks', 'public');
        }

        $validated['user_id'] = Auth::id();

        $task->remarks()->create($validated);

        return back()->with('success', 'Remark added successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    private function notifyAssignee(Task $task, ?User $assignee, User $assigner): void
    {
        if (! $assignee || $assignee->id === $assigner->id) {
            return;
        }

        $remarks = $task->remarks()->with('user')->latest()->get();

        Mail::to($assignee)->send(new TaskAssigned($task, $assignee, $assigner, $remarks));
    }

    private function authorizeTask(Task $task): void
    {
        if ($this->canManageAllTasks(Auth::user())) {
            return;
        }

        if ($task->user_id !== Auth::id() && $task->responsible_user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function canManageAllTasks(mixed $user): bool
    {
        return method_exists($user, 'hasRole')
            && $user->hasRole('super-admin');
    }

    private function storeAttachment(Request $request): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        return $request->file('attachment')->store('task-attachments', 'public');
    }
}
