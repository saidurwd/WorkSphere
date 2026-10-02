@extends('layouts.app')

@section('title', 'Task Notification Logs')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Tasks', 'url' => route('tasks.index')],
        ['label' => 'Notification Logs'],
    ];
@endphp

@section('content')
<x-page-header title="Task Notification Logs" subtitle="Notification logs for task assignments, updates, completions, reminders, and overdue alerts." />

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('tasks.notification-logs.index') }}" method="GET" id="filter-form">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search logs, tasks..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label" for="status">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()" id="status">
                        <option value="">All Statuses</option>
                        <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                        <option value="SENT" {{ ($filters['status'] ?? '') === 'SENT' ? 'selected' : '' }}>Sent</option>
                        <option value="FAILED" {{ ($filters['status'] ?? '') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                        <option value="CANCELLED" {{ ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label" for="channel">Channel:</label>
                    <select name="channel" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()" id="channel">
                        <option value="">All Channels</option>
                        <option value="EMAIL" {{ ($filters['channel'] ?? '') === 'EMAIL' ? 'selected' : '' }}>Email</option>
                        <option value="IN_APP" {{ ($filters['in_app'] ?? '') === 'IN_APP' ? 'selected' : '' }}>In-App</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label" for="notification-type">Type:</label>
                    <select name="notification_type" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()" id="notification-type">
                        <option value="">All Types</option>
                        <option value="task_assigned" {{ ($filters['notification_type'] ?? '') === 'task_assigned' ? 'selected' : '' }}>Task Assigned</option>
                        <option value="task_updated" {{ ($filters['notification_type'] ?? '') === 'task_updated' ? 'selected' : '' }}>Task Updated</option>
                        <option value="task_completed" {{ ($filters['notification_type'] ?? '') === 'task_completed' ? 'selected' : '' }}>Task Completed</option>
                        <option value="task_reminder" {{ ($filters['notification_type'] ?? '') === 'task_reminder' ? 'selected' : '' }}>Task Reminder</option>
                        <option value="task_overdue" {{ ($filters['notification_type'] ?? '') === 'task_overdue' ? 'selected' : '' }}>Task Overdue</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if($logs->count() > 0)
                <button type="button" class="btn btn-danger" data-confirm="Delete all {{ $logs->total() }} notification logs? This cannot be undone." data-confirm-button="Delete all" data-confirm-submit="delete-all-form">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Delete All
                </button>
                @endif

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('tasks.notification-logs.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <form id="delete-all-form" action="{{ route('tasks.notification-logs.destroy-all') }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
            @foreach($filters as $key => $value)
                @if(!empty($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
        </form>
    </div>
</div>

<div class="card">
    @if($logs->count())
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Task</th>
                        <th scope="col">Recipient</th>
                        <th scope="col">Channel</th>
                        <th scope="col">Type</th>
                        <th scope="col">Subject</th>
                        <th scope="col">Status</th>
                        <th scope="col">Scheduled At</th>
                        <th scope="col">Sent At</th>
                        <th scope="col">Actions</th>
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
                        <td><x-badge variant="secondary">{{ $log->channel }}</x-badge></td>
                        <td>{{ $log->notification_type }}</td>
                        <td>{{ $log->subject }}</td>
                        <td>
                            <span class="badge {{ $log->status === 'SENT' ? 'text-bg-success' : ($log->status === 'FAILED' ? 'text-bg-danger' : 'text-bg-warning') }}">
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
                        <td>
                            <form action="{{ route('tasks.notification-logs.destroy', $log) }}" method="POST" style="display: inline;" data-confirm="Are you sure you want to delete this notification log?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" aria-label="Delete" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </td>
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
        <x-empty-state title="No notification logs found" description="No task notification logs available yet." />
    @endif
</div>
@endsection
