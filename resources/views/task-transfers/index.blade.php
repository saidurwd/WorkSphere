@extends('tyro-dashboard::layouts.admin')

@section('title', 'Task Transfers')

@section('breadcrumb')
<a href="{{ \HasinHayder\TyroDashboard\Support\DashboardRoute::name('index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('tasks.index') }}">Tasks</a>
<span class="breadcrumb-separator">/</span>
<span>Task Transfers</span>
@endsection

@section('content')
<style>
    .transfer-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .transfer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Task Transfers</h1>
            <p class="page-description">Reassign tasks between users and keep a transfer history.</p>
        </div>
        <div>
            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5 5-5M18 12H6" />
                </svg>
                Back to Tasks
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <h2 style="margin-bottom: 1rem; font-size: 1.05rem;">New Task Transfer</h2>
        <form action="{{ route('task-transfers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="transfer-grid">
                <div class="form-group">
                    <label for="task_id" class="form-label">Task <span class="text-red-500">*</span></label>
                    <select name="task_id" id="task_id" class="form-select" required>
                        <option value="">Select Task</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}" {{ (old('task_id', $selectedTaskId) == $task->id) ? 'selected' : '' }}>
                                {{ $task->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('task_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="from_user_id" class="form-label">From User</label>
                    <select name="from_user_id" id="from_user_id" class="form-select">
                        <option value="">Current Responsible</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('from_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('from_user_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="to_user_id" class="form-label">To User <span class="text-red-500">*</span></label>
                    <select name="to_user_id" id="to_user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('to_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('to_user_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="transfer_date" class="form-label">Transfer Date</label>
                    <input type="date" name="transfer_date" id="transfer_date" class="form-input"
                        value="{{ old('transfer_date', now()->toDateString()) }}">
                    @error('transfer_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="file_title" class="form-label">File Title</label>
                    <input type="text" name="file_title" id="file_title" class="form-input"
                        placeholder="Attachment title" value="{{ old('file_title') }}">
                    @error('file_title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="file_attache" class="form-label">File Attachment</label>
                    <input type="file" name="file_attache" id="file_attache" class="form-input"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.jpg,.jpeg,.png,.gif">
                    <span class="user-cell-email">Max 10MB. Allowed: pdf, doc, xls, ppt, txt, zip, images.</span>
                    @error('file_attache') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="reason" class="form-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" id="reason" class="form-textarea" rows="3" required
                    placeholder="Why is this task being transferred?">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-textarea" rows="2"
                    placeholder="Optional remarks from the receiver">{{ old('remarks') }}</textarea>
                @error('remarks') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
                    </svg>
                    Transfer Task
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($transfers->count())
        <div class="card-body">
            <h2 style="margin-bottom: 1rem; font-size: 1.05rem;">Transfer History</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Transferred By</th>
                            <th>Reason</th>
                            <th>Transfer Date</th>
                            <th>File</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr>
                                <td>
                                    @if($transfer->task)
                                        <a href="{{ route('tasks.edit', $transfer->task) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                            {{ $transfer->task->title }}
                                        </a>
                                    @else
                                        <span style="color: var(--muted-foreground);">Deleted Task</span>
                                    @endif
                                </td>
                                <td>{{ $transfer->fromUser->name ?? '—' }}</td>
                                <td>{{ $transfer->toUser->name ?? '—' }}</td>
                                <td>{{ $transfer->transferredBy->name ?? '—' }}</td>
                                <td style="max-width: 280px;">
                                    <span title="{{ $transfer->reason }}">{{ Str::limit($transfer->reason, 60) }}</span>
                                    @if($transfer->remarks)
                                        <div class="user-cell-email" title="{{ $transfer->remarks }}">Remarks: {{ Str::limit($transfer->remarks, 40) }}</div>
                                    @endif
                                </td>
                                <td>{{ $transfer->transfer_date ? $transfer->transfer_date->format('M d, Y') : '—' }}</td>
                                <td>
                                    @if($transfer->file_attache)
                                        <a href="{{ Storage::url($transfer->file_attache) }}" target="_blank"
                                            class="action-btn" title="Download {{ $transfer->file_attache }}"
                                            style="color: var(--primary); text-decoration: none;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            {{ $transfer->file_title ?: basename($transfer->file_attache) }}
                                        </a>
                                    @else
                                        <span style="color: var(--muted-foreground);">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons" style="justify-content: flex-end;">
                                        <form action="{{ route('task-transfers.destroy', $transfer) }}" method="POST" style="display: inline;" id="delete-transfer-form-{{ $transfer->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="action-btn action-btn-danger" title="Delete"
                                                onclick="if (confirm('Are you sure you want to delete this transfer record? This action cannot be undone.')) { document.getElementById('delete-transfer-form-{{ $transfer->id }}').submit(); }">
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

            @if($transfers->hasPages())
                <div class="pagination">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
            </svg>
            <h3 class="empty-state-title">No transfers found</h3>
            <p class="empty-state-description">Use the form above to transfer a task to another user.</p>
        </div>
    @endif
</div>
@endsection
