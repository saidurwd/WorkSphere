@extends('tyro-dashboard::layouts.admin')

@section('title', 'Meeting Summary Report')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.reports.index') }}">Reports</a>
<span class="breadcrumb-separator">/</span>
<span>Meeting Summary</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Meeting Summary Report</h1>
            <p class="page-description">View meeting history and status.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.reports.meetings') }}" method="GET">
            <div class="filters-bar">
                <div class="filter-group">
                    <label class="filter-label">Date From:</label>
                    <input type="date" name="date_from" class="form-input" value="{{ $filters['date_from'] ?? '' }}" style="min-width: 150px;">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Date To:</label>
                    <input type="date" name="date_to" class="form-input" value="{{ $filters['date_to'] ?? '' }}" style="min-width: 150px;">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Type:</label>
                    <select name="meeting_type_id" class="form-select" style="min-width: 180px;">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ ($filters['meeting_type_id'] ?? '') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Department:</label>
                    <select name="department_id" class="form-select" style="min-width: 180px;">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ ($filters['department_id'] ?? '') == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Apply Filters</button>
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
                        <th>Minutes Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $meeting)
                    <tr>
                        <td><span style="font-weight: 600; font-family: monospace;">{{ $meeting->meeting_no }}</span></td>
                        <td>{{ $meeting->title }}</td>
                        <td>{{ $meeting->meeting_date->format('M d, Y') }}</td>
                        <td>{{ $meeting->type->name ?? 'N/A' }}</td>
                        <td>{{ $meeting->department->department_name ?? 'N/A' }}</td>
                        <td>{{ $meeting->organizer->name ?? 'N/A' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $meeting->status)) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $meeting->minutes_status)) }}</td>
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
            <p style="margin: 0; color: var(--muted-foreground);">No meetings found.</p>
        </div>
    @endif
</div>
@endsection
