@extends('tyro-dashboard::layouts.admin')

@section('title', $project->name)

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('projects.index') }}">Projects</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $project->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $project->name }}</h1>
            <p class="page-description">Project details and associated tasks.</p>
        </div>
        <div>
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">Edit Project</a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <h3 class="page-title" style="font-size: 1rem;">Description</h3>
        @if($project->description)
            <p style="margin-top: 0.5rem; white-space: pre-wrap;">{{ $project->description }}</p>
        @else
            <p style="margin-top: 0.5rem; color: var(--muted-foreground);">No description provided.</p>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="page-title" style="font-size: 1rem; margin-bottom: 1rem;">Tasks ({{ $tasks->total() }})</h3>
        @if($tasks->count())
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Responsible</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                        <tr>
                            <td>
                                <a href="{{ route('tasks.edit', $task) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $task->title }}
                                </a>
                            </td>
                            <td>
                                @if($task->responsibleUser)
                                    <span style="font-weight: 500;">{{ $task->responsibleUser->name }}</span>
                                @else
                                    <span style="color: var(--muted-foreground);">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $task->priority === 'high' ? 'badge-danger' : ($task->priority === 'medium' ? 'badge-primary' : 'badge-secondary') }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $task->status === 'completed' ? 'badge-success' : ($task->status === 'in_progress' ? 'badge-primary' : 'badge-secondary') }}">
                                    {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                </span>
                            </td>
                            <td>{{ $task->due_date->format('M d, Y') }}</td>
                            <td>
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="{{ route('tasks.edit', $task) }}" class="action-btn" title="Edit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($tasks->hasPages())
            <div class="pagination">
                {{ $tasks->links() }}
            </div>
            @endif
        @else
            <div class="empty-state">
                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <h3 class="empty-state-title">No tasks in this project</h3>
                <p class="empty-state-description">Create a task and assign it to this project.</p>
                <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Task
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
