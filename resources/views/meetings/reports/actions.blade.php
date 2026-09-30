@extends('layouts.app')

@section('title', 'Action Item Report')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Reports', 'url' => route('meetings.reports.index')],
        ['label' => 'Action Items'],
    ];
@endphp

@section('content')
<x-page-header title="Action Item Report" subtitle="Track action item progress." />

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.reports.actions') }}" method="GET">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Meeting:</label>
                    <select name="meeting_id" class="form-select" style="min-width: 180px;">
                        <option value="">All Meetings</option>
                        @foreach($meetings as $meeting)
                            <option value="{{ $meeting->id }}" {{ ($filters['meeting_id'] ?? '') == $meeting->id ? 'selected' : '' }}>{{ $meeting->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Assigned To:</label>
                    <select name="assigned_to" class="form-select" style="min-width: 180px;">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ ($filters['assigned_to'] ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Department:</label>
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
    @if($actions->count())
        <div class="table-responsive">
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
                    @foreach($actions as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->meeting->title ?? 'N/A' }}</td>
                        <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                        <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                        <td>{{ $item->due_date ? $item->due_date->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $item->status)) }}</td>
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

        @if($actions->hasPages())
        <div class="pagination">
            {{ $actions->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No action items found.</p>
        </div>
    @endif
</div>
@endsection
