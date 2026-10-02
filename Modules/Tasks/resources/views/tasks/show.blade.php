@extends('layouts.app')

@php
$priorityClass = $task->priority === 'high' ? 'text-bg-danger' : ($task->priority === 'medium' ? 'text-bg-primary' : 'bg-secondary text-dark');

// `StatusBadge` rather than a hand-rolled ternary. `$task->status` is cast to
// `WorkItemStatus`, so the old `$task->status === 'completed'` compared an enum
// instance to a string and was ALWAYS false: every task rendered with the
// fallback grey badge and the default accent, whatever its status. Two more
// statuses to render would have stayed just as invisible.
$statusClass = \App\Support\StatusBadge::statusBadgeClass($task->status);
$statusAccent = \App\Support\StatusBadge::statusColor($task->status);
@endphp

@section('title', 'Task Details')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Tasks', 'url' => route('tasks.index')],
        ['label' => 'Task Details'],
    ];
@endphp

@section('content')
<x-page-header :title="$task->title" subtitle="Task details and remarks.">
    <x-btn :href="route('tasks.index')" variant="secondary" icon="arrow-left" title="Back to Tasks" />
    <x-btn :href="route('task-transfers.index', ['task_id' => $task->id])" variant="secondary" icon="arrow-left-right" title="Transfer" />
    <x-btn :href="route('tasks.edit', $task)">Edit</x-btn>
    </x-page-header>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">

    {{-- Main column --}}
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Stat cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <div class="card" style="border-left: 4px solid {{ $statusAccent }};">
                <div class="card-body" style="padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Status</div>
                    <div style="margin-top: 0.35rem; font-size: 1.05rem; font-weight: 600; color: var(--card-foreground);">{{ \App\Support\StatusBadge::label($task->status) }}</div>
                </div>
            </div>
            <div class="card" style="border-left: 4px solid var(--primary);">
                <div class="card-body" style="padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Priority</div>
                    <div style="margin-top: 0.35rem; font-size: 1.05rem; font-weight: 600; color: var(--card-foreground);">{{ \App\Support\StatusBadge::label($task->priority) }}</div>
                </div>
            </div>
            <div class="card" style="border-left: 4px solid {{ $task->isOverdue() ? 'var(--danger)' : ($task->isToday() ? 'var(--warning)' : 'var(--success)') }};">
                <div class="card-body" style="padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Due Date</div>
                    <div style="margin-top: 0.35rem; font-size: 1.05rem; font-weight: 600; color: var(--card-foreground);">{{ $task->due_date->format('M d, Y') }}</div>
                </div>
            </div>
            <div class="card" style="border-left: 4px solid var(--info);">
                <div class="card-body" style="padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Transfers</div>
                    <div style="margin-top: 0.35rem; font-size: 1.05rem; font-weight: 600; color: var(--card-foreground);">{{ $task->taskTransfers->count() }}</div>
                </div>
            </div>
        </div>

        {{-- Overview --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Overview</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <span class="badge {{ $priorityClass }}">{{ \App\Support\StatusBadge::label($task->priority) }} Priority</span>
                    <span class="badge {{ $statusClass }}">{{ \App\Support\StatusBadge::label($task->status) }}</span>
                    @if($task->isOverdue())
                    <x-badge variant="danger">Overdue</x-badge>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
                    <div>
                        <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Title</div>
                        <div style="font-size: 1rem; color: var(--card-foreground);">{{ $task->title }}</div>
                    </div>
                    <div>
                        <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Created By</div>
                        <div style="font-size: 1rem; color: var(--card-foreground);">{{ $task->user->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Completed At</div>
                        <div style="font-size: 1rem; color: var(--card-foreground);">{{ $task->completed_at ? $task->completed_at->format('M d, Y h:i A') : '-' }}</div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.5rem; font-size: 0.85rem;">Description</div>
                    <div style="font-size: 0.95rem; color: var(--card-foreground); white-space: pre-wrap; line-height: 1.7; background: var(--muted); padding: 1.25rem; border-radius: var(--radius, 0.625rem); border: 1px solid var(--border);">
                        {{ $task->description ?: 'No description provided.' }}
                    </div>
                </div>

                @if($task->attachment)
                <div style="margin-top: 1.5rem;">
                    <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.5rem; font-size: 0.85rem;">Attachment</div>
                    <a href="{{ asset('storage/' . $task->attachment) }}" target="_blank" class="btn btn-sm btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        {{ basename($task->attachment) }}
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Task Remarks --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Task Remarks</h3>
            </div>
            <div class="card-body" style="padding: 0.5rem 1.25rem 1.25rem;">
                @if($task->remarks->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.5rem;">
                    @foreach($task->remarks as $remark)
                    <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem 1.25rem; background: var(--card); transition: all 0.2s ease;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--info)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9375rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                {{ strtoupper(substr($remark->user->name, 0, 1)) }}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.25rem;">
                                    <div style="font-weight: 600; color: var(--card-foreground); font-size: 0.9375rem;">{{ $remark->user->name }}</div>
                                    <div style="font-size: 0.8125rem; color: var(--muted-foreground); white-space: nowrap;">{{ $remark->created_at->format('M d, Y \a\t H:i') }}</div>
                                </div>
                                <div style="font-size: 0.9375rem; color: var(--foreground); line-height: 1.6; white-space: pre-wrap; word-break: break-word; margin-bottom: 0.75rem;">{{ $remark->remark }}</div>
                                @if($remark->attachment)
                                <a href="{{ asset('storage/' . $remark->attachment) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.875rem; background: var(--muted); border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem; color: var(--foreground); text-decoration: none; transition: all 0.15s ease;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    {{ basename($remark->attachment) }}
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="padding: 3.5rem 1.25rem; text-align: center; color: var(--muted-foreground);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 56px; height: 56px; margin: 0 auto 1rem; opacity: 0.4;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-3.56 8.25-8.25 8.25S4.5 16.556 4.5 12 8.056 3.75 12.75 3.75 21 12z" />
                    </svg>
                    <p style="margin: 0; font-size: 0.9375rem; font-weight: 500;">No remarks yet</p>
                    <p style="margin: 0.5rem 0 0; font-size: 0.875rem; opacity: 0.8;">Click "Add Remark" to add the first one.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar column --}}
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Responsible User</h2>
            </div>
            <div class="card-body">
                @if($task->responsibleUser)
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--info)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem;">
                        {{ strtoupper(substr($task->responsibleUser->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--card-foreground);">{{ $task->responsibleUser->name }}</div>
                        <div style="font-size: 0.85rem; color: var(--muted-foreground);">{{ $task->responsibleUser->email }}</div>
                    </div>
                </div>
                @else
                <span style="color: var(--muted-foreground);">Unassigned</span>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Transfer History</h2>
                <x-badge variant="secondary">{{ $task->taskTransfers->count() }} Transfer(s)</x-badge>
            </div>
            <div class="card-body" style="padding: 0;">
                @if($task->taskTransfers->isNotEmpty())
                <div style="position: relative; padding-left: 1rem;">
                    <div style="position: absolute; left: 17px; top: 8px; bottom: 8px; width: 2px; background: var(--border);"></div>
                    @foreach($task->taskTransfers as $transfer)
                    <div style="display: flex; gap: 1rem; padding-bottom: 1.5rem; position: relative;">
                        <div style="flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; background: var(--info); color: #fff; display: flex; align-items: center; justify-content: center; z-index: 1; box-shadow: 0 0 0 4px var(--card);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
                            </svg>
                        </div>
                        <div style="flex: 1; padding-top: 0.25rem;">
                            <div style="font-weight: 600; color: var(--card-foreground);">
                                {{ $transfer->fromUser->name ?? 'N/A' }}
                                <span style="color: var(--muted-foreground); font-weight: 400; margin: 0 0.35rem;">&rarr;</span>
                                {{ $transfer->toUser->name ?? 'N/A' }}
                            </div>
                            <div style="font-size: 0.85rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                                Transferred by {{ $transfer->transferredBy->name ?? 'N/A' }}
                                &middot; {{ $transfer->transfer_date->format('M d, Y') }}
                            </div>
                            @if($transfer->reason)
                            <div style="font-size: 0.9rem; color: var(--card-foreground); margin-top: 0.5rem;">
                                <span style="font-weight: 500;">Reason:</span> {{ $transfer->reason }}
                            </div>
                            @endif
                            @if($transfer->remarks)
                            <div style="font-size: 0.9rem; color: var(--card-foreground); margin-top: 0.25rem;">
                                <span style="font-weight: 500;">Remarks:</span> {{ $transfer->remarks }}
                            </div>
                            @endif
                            @if($transfer->file_attache)
                            <div style="margin-top: 0.5rem;">
                                <a href="{{ asset('storage/' . $transfer->file_attache) }}" target="_blank" class="btn btn-sm btn-secondary">
                                    {{ $transfer->file_title ?: 'View Attachment' }}
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <x-empty-state title="No transfers yet" description="This task has not been transferred." />
                @endif
            </div>

            {{-- Sub-tasks — GAP-025. The list is a plain card rather than a shared
                 component: the entries carry per-row controls that no existing
                 component renders. --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Sub-tasks ({{ $task->subtasks()->count() }})</h3>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($task->subtasks as $subtask)
                        <li class="list-group-item d-flex align-items-center justify-content-between gap-2">
                            <span>
                                <x-badge :variant="\App\Support\StatusBadge::variant($subtask->status)">
                                    {{ \App\Support\StatusBadge::label($subtask->status) }}
                                </x-badge>
                                {{ $subtask->title }}
                            </span>
                            <span class="text-body-secondary small">
                                {{ $subtask->due_date?->format('M d, Y') ?? 'No date' }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">No sub-tasks yet.</li>
                    @endforelse
                </ul>

                @can('createSubtask', $task)
                    <div class="card-footer">
                        <form action="{{ route('tasks.subtasks.store', $task) }}" method="POST" class="d-flex gap-2">
                            @csrf
                            <label for="subtask-title" class="visually-hidden">Sub-task title</label>
                            <input id="subtask-title" type="text" name="title" class="form-control"
                                   placeholder="Add a sub-task…" maxlength="255" required>
                            <button type="submit" class="btn btn-outline-primary">Add</button>
                        </form>
                    </div>
                @endcan
            </div>

            {{-- Time tracking — GAP-025. `actual_minutes` is a cache of the entries
                 below; both are shown so the figure and its source agree. --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Time</h3>
                    <span class="text-body-secondary small">
                        {{ $task->actual_minutes ?? 0 }} of {{ $task->estimated_minutes ?? 0 }} min
                    </span>
                </div>

                <ul class="list-group list-group-flush">
                    @forelse ($task->timeEntries()->with('user')->orderByDesc('logged_on')->get() as $entry)
                        <li class="list-group-item d-flex align-items-center justify-content-between gap-2">
                            <span>
                                {{ $entry->logged_on->format('M d, Y') }}
                                <span class="text-body-secondary">· {{ $entry->user?->name ?? 'Removed user' }}</span>
                                @if ($entry->note)
                                    <span class="text-body-secondary small d-block">{{ $entry->note }}</span>
                                @endif
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <x-badge variant="secondary">{{ $entry->minutes }} min</x-badge>
                                @can('logTime', $task)
                                    <form action="{{ route('tasks.time-entries.destroy', [$task, $entry]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                                aria-label="Remove time entry for {{ $entry->logged_on->format('M d, Y') }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                @endcan
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">No time logged.</li>
                    @endforelse
                </ul>

                @can('logTime', $task)
                    <div class="card-footer">
                        <form action="{{ route('tasks.time-entries.store', $task) }}" method="POST" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-auto">
                                <label for="entry-minutes" class="form-label">Minutes</label>
                                <input id="entry-minutes" type="number" name="minutes" class="form-control form-control-sm"
                                       min="1" max="1440" value="30" required>
                            </div>
                            <div class="col-auto">
                                <label for="entry-date" class="form-label">Date</label>
                                <input id="entry-date" type="date" name="logged_on" class="form-control form-control-sm"
                                       value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Log</button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>

            {{-- Shared tags — GAP-048. One vocabulary across Tasks, Meetings and
                 To-Dos, so a tag applied here is findable from any of them. --}}
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Tags</h3></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @forelse ($task->tags as $tag)
                        <span class="badge d-inline-flex align-items-center gap-1"
                              style="background-color: {{ $tag->color ?? 'var(--bs-secondary)' }};">
                            {{ $tag->name }}
                            @can('update', $task)
                                <form action="{{ route('tasks.tags.destroy', [$task, $tag]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm p-0 border-0 text-white"
                                            style="line-height: 1;"
                                            aria-label="Remove tag {{ $tag->name }}">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endcan
                        </span>
                    @empty
                        <span class="text-body-secondary">No tags.</span>
                    @endforelse

                    @can('update', $task)
                        <form action="{{ route('tasks.tags.store', $task) }}" method="POST" class="d-flex gap-2 ms-auto">
                            @csrf
                            <label for="task-tag" class="visually-hidden">Add a tag</label>
                            <input id="task-tag" type="text" name="tag" class="form-control form-control-sm"
                                   placeholder="Add a tag…" maxlength="100" required>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Add</button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Activity timeline — GAP-025. Fed by ActivityObserver on Task and by
                 the service-level writes (sub-tasks, time, reparenting). --}}
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Activity</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($activityLog as $entry)
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span class="text-capitalize">{{ str_replace('_', ' ', $entry->action) }}</span>
                            <small class="text-body-secondary">{{ $entry->created_at->diffForHumans() }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">Nothing recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
