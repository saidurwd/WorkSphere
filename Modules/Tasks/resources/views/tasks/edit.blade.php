@extends('layouts.app')

@section('title', 'Edit Task')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Tasks', 'url' => route('tasks.index')],
        ['label' => 'Edit Task'],
    ];

    $taskStatusOptions = \App\Enums\WorkItemStatus::taskOptions();

    // `->value` because `Task::$status` is cast to `WorkItemStatus`. The old
    // comparison was `old('status', $task->status) === 'pending'` — an enum
    // instance against a string — so it was false for every option and the form
    // opened with NOTHING selected, silently defaulting the browser to the first
    // option whatever the task's real status was. Opening a postponed task and
    // saving it would have moved it to pending.
    $currentStatus = old('status', $task->status?->value);
@endphp

@section('content')
<x-page-header title="Edit Task" subtitle="Update task details.">
    <x-btn :href="route('task-transfers.index', ['task_id' => $task->id])" variant="secondary" icon="arrow-left-right">Transfer</x-btn>
    <button type="button" class="btn btn-primary" onclick="openRemarkModal()">Add Remarks</button>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form action="{{ route('tasks.update', $task) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $task->title) }}" required>
                    @error('title') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                    <select name="priority" id="priority" class="form-select" required>
                        <option value="low" {{ old('priority', $task->priority) === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', $task->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority', $task->priority) === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                    @error('priority') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 100%;">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $task->description) }}</textarea>
                    @error('description') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        @foreach($taskStatusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $currentStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', $task->due_date->format('Y-m-d')) }}" required>
                    @error('due_date') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="responsible_user_id" class="form-label">Responsible User <span class="text-danger">*</span></label>
                    <select name="responsible_user_id" id="responsible_user_id" class="form-select" required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('responsible_user_id', $task->responsible_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('responsible_user_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="project_id" class="form-label">Project</label>
                    <select name="project_id" id="project_id" class="form-select">
                        <option value="">No Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 100%;">
                    <label for="attachment" class="form-label">Attachment</label>
                    <input type="file" name="attachment" id="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt">
                    <p class="small mt-1" style="color: var(--muted-foreground);">Maximum file size: 10MB. Allowed: PDF, Word, Excel, images, text.</p>
                    @if($task->attachment)
                        <p class="small mt-1">
                            <a href="{{ asset('storage/' . $task->attachment) }}" target="_blank" class="text-primary text-decoration-underline">
                                {{ basename($task->attachment) }}
                            </a>
                        </p>
                    @endif
                    @error('attachment') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div style="flex: 0 0 100%; display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="update-task-btn">Update Task</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($task->remarks()->exists())
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Task Remarks</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($task->remarks()->with('user')->latest()->get() as $remark)
                <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong>{{ $remark->user->name }}</strong>
                        <span style="font-size: 0.875rem; color: var(--muted-foreground);">{{ $remark->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <p style="margin: 0; color: var(--foreground);">{{ $remark->remark }}</p>
                    @if($remark->attachment)
                        <p class="small mt-2">
                            <a href="{{ asset('storage/' . $remark->attachment) }}" target="_blank" class="text-primary text-decoration-underline">
                                {{ basename($remark->attachment) }}
                            </a>
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Add Remark Modal -->
<div id="remarkModal" class="modal" tabindex="-1" style="display: none; position: fixed; inset: 0; z-index: 999; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 600px; margin: 0;">
        <div class="card-header">
            <h3 class="card-title">Add Remark</h3>
            <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;" onclick="closeRemarkModal()">
                &times;
            </button>
        </div>
        <div class="card-body">
            <form action="{{ route('tasks.remarks.store', $task) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="remark" class="form-label">Remark <span class="text-danger">*</span></label>
                    <textarea name="remark" id="remark" class="form-control" rows="4" required></textarea>
                    @error('remark') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="mb-3">
                    <label for="remark_attachment" class="form-label">Attachment</label>
                    <input type="file" name="remark_attachment" id="remark_attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt">
                    <p class="small mt-1" style="color: var(--muted-foreground);">Maximum file size: 10MB. Allowed: PDF, Word, Excel, images, text.</p>
                    @error('remark_attachment') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeRemarkModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Remark</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openRemarkModal() {
        var modal = document.getElementById('remarkModal');
        modal.style.display = 'flex';
    }

    function closeRemarkModal() {
        var modal = document.getElementById('remarkModal');
        modal.style.display = 'none';
    }

    document.getElementById('remarkModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRemarkModal();
        }
    });
</script>
@endpush
@endsection
