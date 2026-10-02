@extends('layouts.app')

@section('title', 'New Task')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Tasks', 'url' => route('tasks.index')],
        ['label' => 'New Task'],
    ];

    // The statuses a task may take, from the one list the validation rules and the
    // schema read. The dropdown used to carry its own three hardcoded <option>s.
    $taskStatusOptions = \App\Enums\WorkItemStatus::taskOptions();
@endphp

@section('content')
<x-page-header title="New Task" subtitle="Create a new task." />

<div class="card">
    <div class="card-body">
        <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                    @error('title') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                    <select name="priority" id="priority" class="form-select" required>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                    @error('priority') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 100%;">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                    @error('description') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        @foreach($taskStatusOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'pending') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date') }}" required>
                    @error('due_date') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3" style="flex: 0 0 calc(50% - 0.5rem);">
                    <label for="responsible_user_id" class="form-label">Responsible User <span class="text-danger">*</span></label>
                    <select name="responsible_user_id" id="responsible_user_id" class="form-select" required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('responsible_user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
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
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
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
                    @error('attachment') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div style="flex: 0 0 100%; display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="create-task-btn">Create Task</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        var btn = document.getElementById('create-task-btn');
        if (!btn) return;

        var form = btn.closest('form');
        if (!form) return;

        btn.addEventListener('click', function(e) {
            if (!form.checkValidity()) {
                return;
            }

            e.preventDefault();

            btn.disabled = true;
            btn.innerHTML = 'Working...<svg style="width:1.25rem;height:1.25rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path></svg>';

            form.submit();
        });
    })();
</script>
@endpush
@endsection
