@extends('tyro-dashboard::layouts.admin')

@php
$priorityClass = $task->priority === 'high' ? 'badge-danger' : ($task->priority === 'medium' ? 'badge-primary' : 'badge-secondary');
$statusClass = $task->status === 'completed' ? 'badge-success' : ($task->status === 'in_progress' ? 'badge-primary' : 'badge-secondary');
$statusAccent = $task->status === 'completed' ? 'var(--success)' : ($task->status === 'in_progress' ? 'var(--info)' : ($task->status === 'pending' ? 'var(--warning)' : 'var(--primary)'));
@endphp

@section('title', 'Task Details')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('tasks.index') }}">Tasks</a>
<span class="breadcrumb-separator">/</span>
<span>Task Details</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="{{ route('tasks.index') }}" class="btn btn-ghost" title="Back to Tasks">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="page-title">{{ $task->title }}</h1>
                <p class="page-description">Task details and remarks.</p>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('task-transfers.index', ['task_id' => $task->id]) }}" class="btn btn-secondary" title="Transfer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
                </svg>
                Transfer
            </a>
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">Edit</a>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">

    {{-- Main column --}}
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Stat cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <div class="card" style="border-left: 4px solid {{ $statusAccent }};">
                <div class="card-body" style="padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Status</div>
                    <div style="margin-top: 0.35rem; font-size: 1.05rem; font-weight: 600; color: var(--card-foreground);">{{ ucwords(str_replace('_', ' ', $task->status)) }}</div>
                </div>
            </div>
            <div class="card" style="border-left: 4px solid var(--primary);">
                <div class="card-body" style="padding: 1rem 1.25rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Priority</div>
                    <div style="margin-top: 0.35rem; font-size: 1.05rem; font-weight: 600; color: var(--card-foreground);">{{ ucfirst($task->priority) }}</div>
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
                    <span class="badge {{ $priorityClass }}">{{ ucfirst($task->priority) }} Priority</span>
                    <span class="badge {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $task->status)) }}</span>
                    @if($task->isOverdue())
                    <span class="badge badge-danger">Overdue</span>
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
                <span class="badge badge-secondary">{{ $task->taskTransfers->count() }} Transfer(s)</span>
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
                <div class="empty-state" style="padding: 2.5rem 0;">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; margin: 0 auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
                    </svg>
                    <h3 class="empty-state-title">No transfers yet</h3>
                    <p class="empty-state-description">This task has not been transferred.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection