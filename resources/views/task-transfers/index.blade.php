@extends('layouts.app')

@section('title', 'Task Transfers')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Tasks', 'url' => route('tasks.index')],
        ['label' => 'Task Transfers'],
    ];
@endphp

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

<x-page-header title="Task Transfers" subtitle="Reassign tasks between users and keep a transfer history.">
    <x-btn :href="route('tasks.index')" variant="secondary" icon="arrow-left">Back to Tasks</x-btn>
</x-page-header>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <h2 style="margin-bottom: 1rem; font-size: 1.05rem;">New Task Transfer</h2>
        <form action="{{ route('task-transfers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="transfer-grid">
                <div class="mb-3">
                    <label for="task_id" class="form-label">Task <span class="text-danger">*</span></label>
                    <select name="task_id" id="task_id" class="form-select" required>
                        <option value="">Select Task</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}" {{ (old('task_id', $selectedTaskId) == $task->id) ? 'selected' : '' }}>
                                {{ $task->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('task_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label for="from_user_id" class="form-label">From User</label>
                    <select name="from_user_id" id="from_user_id" class="form-select">
                        <option value="">Current Responsible</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('from_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('from_user_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label for="to_user_id" class="form-label">To User <span class="text-danger">*</span></label>
                    <select name="to_user_id" id="to_user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('to_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('to_user_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label for="transfer_date" class="form-label">Transfer Date</label>
                    <input type="date" name="transfer_date" id="transfer_date" class="form-control"
                        value="{{ old('transfer_date', now()->toDateString()) }}">
                    @error('transfer_date') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label for="file_title" class="form-label">File Title</label>
                    <input type="text" name="file_title" id="file_title" class="form-control"
                        placeholder="Attachment title" value="{{ old('file_title') }}">
                    @error('file_title') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label for="file_attache" class="form-label">File Attachment</label>
                    <input type="file" name="file_attache" id="file_attache" class="form-control"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.jpg,.jpeg,.png,.gif">
                    <span class="small text-body-secondary">Max 10MB. Allowed: pdf, doc, xls, ppt, txt, zip, images.</span>
                    @error('file_attache') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" id="reason" class="form-control" rows="3" required
                    placeholder="Why is this task being transferred?">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="2"
                    placeholder="Optional remarks from the receiver">{{ old('remarks') }}</textarea>
                @error('remarks') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
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
        <div class="card-header">
            <h3 class="card-title mb-0">Transfer History</h3>
        </div>

        <div class="card-body p-0">
            <x-datatable id="transfers-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                <thead>
                    <tr>
                        <th scope="col">Task</th>
                        <th scope="col">From</th>
                        <th scope="col">To</th>
                        <th scope="col">Transferred By</th>
                        <th scope="col">Reason</th>
                        <th scope="col">Transfer Date</th>
                        <th scope="col">File</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfers as $transfer)
                        <tr>
                            <td>
                                @if($transfer->task)
                                    <a href="{{ route('tasks.edit', $transfer->task) }}" class="fw-semibold text-decoration-none">
                                        {{ $transfer->task->title }}
                                    </a>
                                @else
                                    <span class="text-body-secondary">Deleted Task</span>
                                @endif
                            </td>
                            <td>{{ $transfer->fromUser->name ?? '—' }}</td>
                            <td>{{ $transfer->toUser->name ?? '—' }}</td>
                            <td>{{ $transfer->transferredBy->name ?? '—' }}</td>
                            <td>{{ Str::limit($transfer->reason, 60) }}</td>
                            <td>{{ $transfer->transfer_date ? $transfer->transfer_date->format('M d, Y') : '—' }}</td>
                            <td>
                                @if($transfer->file_attache)
                                    <a href="{{ Storage::url($transfer->file_attache) }}" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-file-earmark-text me-1"></i>{{ $transfer->file_title ?: basename($transfer->file_attache) }}
                                    </a>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <form action="{{ route('task-transfers.destroy', $transfer) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                data-confirm="Delete this transfer record? This cannot be undone."
                                                data-confirm-button="Delete" aria-label="Delete" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-datatable>
        </div>

        @if ($transfers->hasPages())
            <div class="card-footer">
                <x-pagination :paginator="$transfers" />
            </div>
        @endif
    @else
        <div class="card-body">
            <x-empty-state icon="arrow-left-right" title="No transfers found" description="Use the form above to transfer a task to another user." />
        </div>
    @endif
</div>
@endsection
