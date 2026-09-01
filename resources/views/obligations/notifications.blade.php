@extends('tyro-dashboard::layouts.admin')

@section('title', 'Notifications')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>Notifications</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligation Notifications</h1>
            <p class="page-description">Notification logs for reminders and escalations.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.notifications') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search notifications or obligations..." value="{{ $filters['search'] ?? '' }}">
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
                        <option value="IN_APP" {{ ($filters['channel'] ?? '') === 'IN_APP' ? 'selected' : '' }}>In-App</option>
                        <option value="EMAIL" {{ ($filters['channel'] ?? '') === 'EMAIL' ? 'selected' : '' }}>Email</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if($notifications->count() > 0)
                <button type="button" class="btn btn-danger" onclick="if (confirm('Are you sure you want to delete all {{ $notifications->total() }} notification(s)?')) { document.getElementById('delete-all-form').submit(); }">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Delete All
                </button>
                @endif

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.notifications') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>

        <form id="delete-all-form" action="{{ route('obligations.notifications.destroy-all') }}" method="POST" style="display: none;">
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
    @if($notifications->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Obligation</th>
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
                    @foreach($notifications as $notification)
                    <tr>
                        <td>
                            @if($notification->obligation)
                                <a href="{{ route('obligations.show', $notification->obligation) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $notification->obligation->obligation_no }} - {{ $notification->obligation->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
                            @endif
                        </td>
                        <td>{{ $notification->user->name ?? 'N/A' }}</td>
                        <td><span class="badge badge-secondary">{{ $notification->channel }}</span></td>
                        <td>{{ $notification->notification_type }}</td>
                        <td>{{ $notification->subject }}</td>
                        <td>
                            <span class="badge {{ $notification->status === 'SENT' ? 'badge-success' : ($notification->status === 'FAILED' ? 'badge-danger' : 'badge-warning') }}">
                                {{ $notification->status }}
                            </span>
                        </td>
                        <td>{{ $notification->scheduled_at ? $notification->scheduled_at->format('M d, Y H:i') : 'N/A' }}</td>
                         <td>{{ $notification->sent_at ? $notification->sent_at->format('M d, Y H:i') : 'N/A' }}</td>
                        <td>
                            <form action="{{ route('obligations.notifications.destroy', $notification) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
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

        @if($notifications->hasPages())
        <div class="pagination">
            {{ $notifications->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            <h3 class="empty-state-title">No notifications found</h3>
            <p class="empty-state-description">No notification logs available yet.</p>
        </div>
    @endif
</div>
@endsection
