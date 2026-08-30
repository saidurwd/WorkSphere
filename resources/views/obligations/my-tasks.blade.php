@extends('tyro-dashboard::layouts.admin')

@section('title', 'My Tasks')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>My Tasks</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">My Obligation Tasks</h1>
            <p class="page-description">Tasks assigned to you related to compliance obligations.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.my-tasks') }}" method="GET" id="filter-form">
            <div class="filters-bar">
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
                    <label class="filter-label">Due Date:</label>
                    <select name="due_date" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Dates</option>
                        <option value="today" {{ ($filters['due_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="overdue" {{ ($filters['due_date'] ?? '') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="upcoming" {{ ($filters['due_date'] ?? '') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.my-tasks') }}" class="btn btn-ghost">Clear</a>
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
                        <th>Task No.</th>
                        <th>Title</th>
                        <th>Obligation</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr>
                        <td>{{ $task->task_no ?? 'N/A' }}</td>
                        <td>{{ $task->title }}</td>
                        <td>
                            @if($task->obligation)
                                <a href="{{ route('obligations.show', $task->obligation) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $task->obligation->obligation_no }} - {{ $task->obligation->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
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
                                @if($task->obligation)
                                    <a href="{{ route('obligations.show', $task->obligation) }}" class="action-btn" title="View Obligation">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V8.25A2.25 2.25 0 0016.5 6z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6l3-3m0 0l3 3m-3-3v12" />
                                        </svg>
                                    </a>
                                @endif
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
            <p class="empty-state-description">You have no tasks related to obligations.</p>
        </div>
    @endif
</div>
@endsection
