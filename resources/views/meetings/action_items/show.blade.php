@extends('tyro-dashboard::layouts.admin')

@section('title', 'Action Item Details')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.action-items.index') }}">Action Items</a>
<span class="breadcrumb-separator">/</span>
<span>Details</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $actionItem->title }}</h1>
            <p class="page-description">Action item details and linked task.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Status</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ ucwords(str_replace('_', ' ', $actionItem->status)) }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Priority</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ ucfirst($actionItem->priority) }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Assigned To</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $actionItem->assignedTo->name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Department</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $actionItem->assignedDepartment->department_name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Due Date</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $actionItem->due_date ? $actionItem->due_date->format('M d, Y') : 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Completion</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $actionItem->completion_percentage }}%</div>
            </div>
        </div>

        @if($actionItem->description)
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
            <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.5rem; font-size: 0.85rem;">Description</div>
            <div style="white-space: pre-wrap; line-height: 1.7;">{{ $actionItem->description }}</div>
        </div>
        @endif
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Linked Task</h2>
    </div>
    <div class="card-body">
        @if($actionItem->task)
            <a href="{{ route('tasks.show', $actionItem->task) }}" style="font-weight: 600;">{{ $actionItem->task->task_no ?? 'Task #'.$actionItem->task->id }} - {{ $actionItem->task->title }}</a>
            <div style="color: var(--muted-foreground); margin-top: 0.25rem; font-size: 0.9rem;">Status: {{ ucwords(str_replace('_', ' ', $actionItem->task->status)) }}</div>
        @else
            <p style="color: var(--muted-foreground);">No task linked to this action item.</p>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Meeting Context</h2>
    </div>
    <div class="card-body">
        <a href="{{ route('meetings.show', $actionItem->meeting) }}" style="font-weight: 600;">{{ $actionItem->meeting->title ?? 'N/A' }}</a>
        <div style="color: var(--muted-foreground); margin-top: 0.25rem; font-size: 0.9rem;">{{ $actionItem->meeting->meeting_no ?? '' }}</div>
    </div>
</div>
@endsection
