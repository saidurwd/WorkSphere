@extends('tyro-dashboard::layouts.admin')

@section('title', 'Tasks')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Tasks</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Tasks</h1>
            <p class="page-description">Manage your tasks and track progress.</p>
        </div>
        <div>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Task
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('tasks.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search tasks..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Priority:</label>
                    <select name="priority" class="form-select" style="min-width: 140px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Priorities</option>
                        <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Responsible:</label>
                    <select name="responsible_user_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Responsible Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (int) ($filters['responsible_user_id'] ?? 0) === $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Due Date:</label>
                    <select name="due_date" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Dates</option>
                        <option value="today" {{ ($filters['due_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ ($filters['due_date'] ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($filters['due_date'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="future" {{ ($filters['due_date'] ?? '') === 'future' ? 'selected' : '' }}>Future</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Project:</label>
                    <select name="project_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ ($filters['project_id'] ?? '') === (string) $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['priority']) || !empty($filters['due_date']) || !empty($filters['responsible_user_id']) || !empty($filters['project_id']))
                    <a href="{{ route('tasks.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($tasks->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Responsible</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr>
                        <td>
                            <a href="{{ route('tasks.edit', $task) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                {{ $task->title }}
                            </a>
                            @if($task->project)
                                <div class="user-cell-email">{{ $task->project->name }}</div>
                            @endif
                            @if($task->taskTransfers->isNotEmpty())
                                <div class="user-cell-email">Transferred Task</div>
                            @endif
                        </td>
                        <td>
                            @if($task->responsibleUser)
                                <span style="font-weight: 500;">{{ $task->responsibleUser->name }}</span>
                                <div class="user-cell-email">{{ $task->responsibleUser->email }}</div>
                            @else
                                <span style="color: var(--muted-foreground);">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $task->priority === 'high' ? 'badge-danger' : ($task->priority === 'medium' ? 'badge-primary' : 'badge-secondary') }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $task->status === 'completed' ? 'badge-success' : ($task->status === 'in_progress' ? 'badge-primary' : 'badge-secondary') }}">
                                {{ ucwords(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </td>
                        <td>{{ $task->due_date->format('M d, Y') }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('tasks.show', $task) }}" class="action-btn" title="Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('task-transfers.index', ['task_id' => $task->id]) }}" class="action-btn" title="Transfer">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
                                    </svg>
                                </a>
                                <a href="{{ route('tasks.edit', $task) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" style="display: inline;" id="delete-task-form-{{ $task->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) { document.getElementById('delete-task-form-{{ $task->id }}').submit(); }">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($tasks->hasPages())
        <div class="pagination">
            {{ $tasks->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <h3 class="empty-state-title">No tasks found</h3>
            <p class="empty-state-description">Get started by creating a new task.</p>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Task
            </a>
        </div>
    @endif
</div>
@endsection
