@extends('tyro-dashboard::layouts.admin')

@section('title', 'Action Items')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<span>Action Items</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Action Items</h1>
            <p class="page-description">Track meeting action items and tasks.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.action-items.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search action items..." value="{{ request('search') }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Priority:</label>
                    <select name="priority" class="form-select" style="min-width: 140px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Priorities</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Meeting:</label>
                    <select name="meeting_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Meetings</option>
                        @foreach($meetings as $meeting)
                            <option value="{{ $meeting->id }}" {{ request('meeting_id') == $meeting->id ? 'selected' : '' }}>{{ $meeting->title }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>
                @if(request()->hasAny(['search', 'status', 'priority', 'meeting_id', 'overdue']))
                    <a href="{{ route('meetings.action-items.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($actionItems->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Meeting</th>
                        <th>Assigned To</th>
                        <th>Department</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Task</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actionItems as $item)
                    <tr>
                        <td>
                            <a href="{{ route('meetings.action-items.show', $item) }}" style="font-weight: 500; text-decoration: none; color: inherit;">{{ $item->title }}</a>
                        </td>
                        <td>{{ $item->meeting->title ?? 'N/A' }}</td>
                        <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                        <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                        <td>{{ $item->due_date ? $item->due_date->format('M d, Y') : 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $item->status === 'completed' ? 'badge-success' : ($item->status === 'in_progress' ? 'badge-primary' : ($item->status === 'on_hold' ? 'badge-warning' : 'badge-secondary')) }}">
                                {{ ucwords(str_replace('_', ' ', $item->status)) }}
                            </span>
                            @if($item->isOverdue())
                            <span class="badge badge-danger" style="margin-left: 0.25rem;">Overdue</span>
                            @endif
                        </td>
                        <td>
                            @if($item->task)
                            <a href="{{ route('tasks.show', $item->task) }}">{{ $item->task->task_no ?? 'Task #'.$item->task->id }}</a>
                            @else
                            <span style="color: var(--muted-foreground);">Not linked</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($actionItems->hasPages())
        <div class="pagination">
            {{ $actionItems->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <h3 class="empty-state-title">No action items found</h3>
        </div>
    @endif
</div>
@endsection
