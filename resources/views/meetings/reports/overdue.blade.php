@extends('tyro-dashboard::layouts.admin')

@section('title', 'Overdue Actions')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.reports.index') }}">Reports</a>
<span class="breadcrumb-separator">/</span>
<span>Overdue Actions</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Overdue Actions</h1>
            <p class="page-description">Action items that are past due.</p>
        </div>
    </div>
</div>

<div class="card">
    @if($overdue->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Meeting</th>
                        <th>Assigned To</th>
                        <th>Department</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Task</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdue as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->meeting->title ?? 'N/A' }}</td>
                        <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                        <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                        <td>{{ $item->due_date->format('M d, Y') }}</td>
                        <td><span class="badge badge-danger">{{ now()->startOfDay()->diffInDays($item->due_date) }} days</span></td>
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
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="empty-state-title">No overdue actions</h3>
            <p class="empty-state-description">All action items are on track.</p>
        </div>
    @endif
</div>
@endsection
