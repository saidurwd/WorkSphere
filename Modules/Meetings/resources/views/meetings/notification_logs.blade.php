@extends('layouts.app')

@section('title', 'Meeting Notification Logs')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Notification Logs'],
    ];
@endphp

@section('content')
<x-page-header title="Meeting Notification Logs" subtitle="Notification logs for meeting invitations, updates, cancellations, minutes, and action items." />

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.notification-logs.index') }}" method="GET" id="filter-form">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search logs, meetings, action items..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                        <option value="SENT" {{ ($filters['status'] ?? '') === 'SENT' ? 'selected' : '' }}>Sent</option>
                        <option value="FAILED" {{ ($filters['status'] ?? '') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                        <option value="CANCELLED" {{ ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Channel:</label>
                    <select name="channel" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Channels</option>
                        <option value="EMAIL" {{ ($filters['channel'] ?? '') === 'EMAIL' ? 'selected' : '' }}>Email</option>
                        <option value="IN_APP" {{ ($filters['in_app'] ?? '') === 'IN_APP' ? 'selected' : '' }}>In-App</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Type:</label>
                    <select name="notification_type" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Types</option>
                        <option value="meeting_invitation" {{ ($filters['notification_type'] ?? '') === 'meeting_invitation' ? 'selected' : '' }}>Meeting Invitation</option>
                        <option value="meeting_updated" {{ ($filters['notification_type'] ?? '') === 'meeting_updated' ? 'selected' : '' }}>Meeting Updated</option>
                        <option value="meeting_cancelled" {{ ($filters['notification_type'] ?? '') === 'meeting_cancelled' ? 'selected' : '' }}>Meeting Cancelled</option>
                        <option value="action_assigned" {{ ($filters['notification_type'] ?? '') === 'action_assigned' ? 'selected' : '' }}>Action Assigned</option>
                        <option value="action_completed" {{ ($filters['notification_type'] ?? '') === 'action_completed' ? 'selected' : '' }}>Action Completed</option>
                        <option value="action_reminder" {{ ($filters['notification_type'] ?? '') === 'action_reminder' ? 'selected' : '' }}>Action Reminder</option>
                        <option value="action_overdue" {{ ($filters['notification_type'] ?? '') === 'action_overdue' ? 'selected' : '' }}>Action Overdue</option>
                        <option value="minutes_submitted" {{ ($filters['notification_type'] ?? '') === 'minutes_submitted' ? 'selected' : '' }}>Minutes Submitted</option>
                        <option value="minutes_approved" {{ ($filters['notification_type'] ?? '') === 'minutes_approved' ? 'selected' : '' }}>Minutes Approved</option>
                        <option value="minutes_returned" {{ ($filters['notification_type'] ?? '') === 'minutes_returned' ? 'selected' : '' }}>Minutes Returned</option>
                        <option value="minutes_published" {{ ($filters['notification_type'] ?? '') === 'minutes_published' ? 'selected' : '' }}>Minutes Published</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if($logs->count() > 0)
                <button type="button" class="btn btn-danger" onclick="if (confirm('Are you sure you want to delete all {{ $logs->total() }} notification log(s)?')) { document.getElementById('delete-all-form').submit(); }">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Delete All
                </button>
                @endif

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('meetings.notification-logs.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <form id="delete-all-form" action="{{ route('meetings.notification-logs.destroy-all') }}" method="POST" style="display: none;">
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
                        <th>Meeting</th>
                        <th>Action Item</th>
                        <th>Recipient</th>
                        <th>Channel</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Scheduled At</th>
                        <th>Sent At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>
                            @if($log->meeting)
                                <a href="{{ route('meetings.show', $log->meeting) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $log->meeting->meeting_no }} - {{ $log->meeting->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($log->actionItem)
                                <span style="font-weight: 500;">{{ $log->actionItem->title }}</span>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
                            @endif
                        </td>
                        <td>{{ $log->user->name ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary text-dark">{{ $log->channel }}</span></td>
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
                            <form action="{{ route('meetings.notification-logs.destroy', $log) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification log?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
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
        <x-empty-state title="No notification logs found" description="No meeting notification logs available yet." />
    @endif
</div>
@endsection
