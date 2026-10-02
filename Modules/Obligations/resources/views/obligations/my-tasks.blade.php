@extends('layouts.app')

@section('title', 'My Tasks')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations', 'url' => route('obligations.dashboard')],
        ['label' => 'My Tasks'],
    ];
@endphp

@section('content')
<x-page-header title="My Obligation Tasks" subtitle="Tasks assigned to you related to compliance obligations." />

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.my-tasks') }}" method="GET" id="filter-form">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        {{-- These are TASK rows, so the task vocabulary. --}}
                        @foreach(\App\Enums\WorkItemStatus::taskOptions() as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Priority:</label>
                    <select name="priority" class="form-select" style="min-width: 140px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Priorities</option>
                        <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Due Date:</label>
                    <select name="due_date" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Dates</option>
                        <option value="today" {{ ($filters['due_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="overdue" {{ ($filters['due_date'] ?? '') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="upcoming" {{ ($filters['due_date'] ?? '') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.my-tasks') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($tasks->count())
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task No.</th>
                        <th>Title</th>
                        <th>Obligation</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                    <tr>
                        <td>{{ $task->task_no ?? 'N/A' }}</td>
                        <td>{{ $task->title }}</td>
                        <td>
                            @if($task->obligation)
                                <a href="{{ route('obligations.show', $task->obligation) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $task->obligation->obligation_no }} - {{ $task->obligation->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
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
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                @if($task->obligation)
                                    <a href="{{ route('obligations.show', $task->obligation) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="View Obligation">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V8.25A2.25 2.25 0 0016.5 6z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6l3-3m0 0l3 3m-3-3v12" />
                                        </svg>
                                    </a>
                                @endif
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
        <x-empty-state title="No tasks found" description="You have no tasks related to obligations." />
    @endif
</div>
@endsection
