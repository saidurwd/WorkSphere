@extends('layouts.app')

@section('title', $project->name)

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Projects', 'url' => route('projects.index')],
        ['label' => $project->name],
    ];
@endphp

@section('content')
<x-page-header :title="$project->name" subtitle="Project details and associated tasks.">
    <x-btn :href="route('projects.edit', $project)" variant="secondary">Edit Project</x-btn>
</x-page-header>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <h3 class="h6">Description</h3>
        @if($project->description)
            <p style="margin-top: 0.5rem; white-space: pre-wrap;">{{ $project->description }}</p>
        @else
            <p style="margin-top: 0.5rem; color: var(--muted-foreground);">No description provided.</p>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="h6 mb-3">Tasks ({{ $tasks->total() }})</h3>
        @if($tasks->count())
            <div class="table-responsive">
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
                                <span class="badge {{ $task->priority === 'high' ? 'text-bg-danger' : ($task->priority === 'medium' ? 'badge-primary' : 'text-bg-secondary') }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge text-bg-{{ \App\Support\StatusBadge::variant($task->status) }}">
                                    {{ \App\Support\StatusBadge::label($task->status) }}
                                </span>
                            </td>
                            <td>{{ $task->due_date->format('M d, Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-1" style="justify-content: flex-end;">
                                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Edit">
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
            <x-empty-state title="No tasks in this project" description="Create a task and assign it to this project.">
    <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        New Task
                    </a>
</x-empty-state>
        @endif
    </div>
</div>
@endsection
