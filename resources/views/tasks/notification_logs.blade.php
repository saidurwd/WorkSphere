@extends('tyro-dashboard::layouts.admin')

@section('title', 'Task Notification Logs')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('tasks.index') }}">Tasks</span>
<span class="breadcrumb-separator">/</span>
<span>Notification Logs</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Task Notification Logs</h1>
            <p class="page-description">Notification logs for task assignments, updates, completions, reminders, and overdue alerts.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('tasks.notification-logs.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search logs, tasks..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                        <option value="SENT" {{ ($filters['status'] ?? '') === 'SENT' ? 'selected' : '' }}>Sent</option>
                        <option value="FAILED" {{ ($filters['status'] ?? '') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                        <option value="CANCELLED" {{ ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Channel:</label>
                    <select name="channel" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Channels</option>
                        <option value="EMAIL" {{ ($filters['channel'] ?? '') === 'EMAIL' ? 'selected' : '' }}>Email</option>
                        <option value="IN_APP" {{ ($filters['in_app'] ?? '') === 'IN_APP' ? 'selected' : '' }}>In-App</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Type:</label>
                    <select name="notification_type" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Types</option>
                        <option value="task_assigned" {{ ($filters['notification_type'] ?? '') === 'task_assigned' ? 'selected' : '' }}>Task Assigned</option>
                        <option value="task_updated" {{ ($filters['notification_type'] ?? '') === 'task_updated' ? 'selected' : '' }}>Task Updated</option>
                        <option value="task_completed" {{ ($filters['notification_type'] ?? '') === 'task_completed' ? 'selected' : '' }}>Task Completed</option>
                        <option value="task_reminder" {{ ($filters['notification_type'] ?? '') === 'task_reminder' ? 'selected' : '' }}>Task Reminder</option>
                        <option value="task_overdue" {{ ($filters['notification_type'] ?? '') === 'task_overdue' ? 'selected' : '' }}>Task Overdue</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('tasks.notification-logs.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($logs->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Recipient</th>
                        <th>Channel</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Scheduled At</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>
                            @if($log->task)
                                <a href="{{ route('tasks.show', $log->task) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $log->task->task_no }} - {{ $log->task->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
                            @endif
                        </td>
                        <td>{{ $log->user->name ?? 'N/A' }}</td>
                        <td><span class="badge badge-secondary">{{ $log->channel }}</span></td>
                        <td>{{ $log->notification_type }}</td>
                        <td>{{ $log->subject }}</td>
                        <td>
                            <span class="badge {{ $log->status === 'SENT' ? 'badge-success' : ($log->status === 'FAILED' ? 'badge-danger' : 'badge-warning') }}">
                                {{ $log->status }}
                            </span>
                            @if($log->error_message)
                                <div style="font-size: 0.75rem; color: var(--destructive); margin-top: 0.25rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->error_message }}">
                                    {{ $log->error_message }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $log->scheduled_at ? $log->scheduled_at->format('M d, Y H:i') : 'N/A' }}</td>
                        <td>{{ $log->sent_at ? $log->sent_at->format('M d, Y H:i') : 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="pagination">
            {{ $logs->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            <h3 class="empty-state-title">No notification logs found</h3>
            <p class="empty-state-description">No task notification logs available yet.</p>
        </div>
    @endif
</div>
@endsection
