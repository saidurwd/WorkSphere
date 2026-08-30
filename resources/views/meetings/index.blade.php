@extends('tyro-dashboard::layouts.admin')

@section('title', 'Meetings')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Meetings</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Meetings</h1>
            <p class="page-description">Schedule and manage meetings.</p>
        </div>
        <div>
            <a href="{{ route('meetings.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Meeting
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search meetings..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="scheduled" {{ ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="postponed" {{ ($filters['status'] ?? '') === 'postponed' ? 'selected' : '' }}>Postponed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Type:</label>
                    <select name="meeting_type_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ (int) ($filters['meeting_type_id'] ?? 0) === $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Department:</label>
                    <select name="department_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ (int) ($filters['department_id'] ?? 0) === $department->id ? 'selected' : '' }}>
                                {{ $department->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date From:</label>
                    <input type="date" name="date_from" class="form-input" value="{{ $filters['date_from'] ?? '' }}" style="min-width: 150px;">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date To:</label>
                    <input type="date" name="date_to" class="form-input" value="{{ $filters['date_to'] ?? '' }}" style="min-width: 150px;">
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['meeting_type_id']) || !empty($filters['department_id']) || !empty($filters['date_from']) || !empty($filters['date_to']))
                    <a href="{{ route('meetings.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($meetings->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Meeting No</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Organizer</th>
                        <th>Status</th>
                        <th>Minutes</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $meeting)
                    <tr>
                        <td><span style="font-weight: 600; font-family: monospace;">{{ $meeting->meeting_no }}</span></td>
                        <td>
                            <a href="{{ route('meetings.show', $meeting) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                {{ $meeting->title }}
                            </a>
                        </td>
                        <td>{{ $meeting->meeting_date->format('M d, Y') }}</td>
                        <td>{{ $meeting->type->name ?? 'N/A' }}</td>
                        <td>{{ $meeting->department->department_name ?? 'N/A' }}</td>
                        <td>{{ $meeting->organizer->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $meeting->status === 'completed' ? 'badge-success' : ($meeting->status === 'cancelled' ? 'badge-danger' : ($meeting->status === 'in_progress' ? 'badge-primary' : 'badge-secondary')) }}">
                                {{ ucwords(str_replace('_', ' ', $meeting->status)) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $meeting->minutes_status === 'published' ? 'badge-success' : ($meeting->minutes_status === 'approved' ? 'badge-primary' : ($meeting->minutes_status === 'submitted' ? 'badge-warning' : 'badge-secondary')) }}">
                                {{ ucwords(str_replace('_', ' ', $meeting->minutes_status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('meetings.show', $meeting) }}" class="action-btn" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('meetings.edit', $meeting) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" style="display: inline;" id="delete-meeting-form-{{ $meeting->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this meeting? This action cannot be undone.')) { document.getElementById('delete-meeting-form-{{ $meeting->id }}').submit(); }">
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

        @if($meetings->hasPages())
        <div class="pagination">
            {{ $meetings->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="empty-state-title">No meetings found</h3>
            <p class="empty-state-description">Get started by scheduling a new meeting.</p>
            <a href="{{ route('meetings.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Meeting
            </a>
        </div>
    @endif
</div>
@endsection
