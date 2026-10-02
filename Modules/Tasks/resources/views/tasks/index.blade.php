@extends('layouts.app')

@section('title', 'Tasks')

@section('header-actions')
    <x-btn :href="route('tasks.create')" icon="plus-lg">New Task</x-btn>
@endsection

@section('content')
    <x-page-header title="Tasks" subtitle="Manage your tasks and track progress." icon="check2-square" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('tasks.index') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="filter-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="filter-search" type="search" name="search" class="form-control"
                                   placeholder="Search tasks..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-status" class="form-label">Status</label>
                        <select id="filter-status" name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach(\App\Enums\WorkItemStatus::taskOptions() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-priority" class="form-label">Priority</label>
                        <select id="filter-priority" name="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="low" @selected(($filters['priority'] ?? '') === 'low')>Low</option>
                            <option value="medium" @selected(($filters['priority'] ?? '') === 'medium')>Medium</option>
                            <option value="high" @selected(($filters['priority'] ?? '') === 'high')>High</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-responsible" class="form-label">Responsible</label>
                        <select id="filter-responsible" name="responsible_user_id" class="form-select">
                            <option value="">All Users</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((int) ($filters['responsible_user_id'] ?? 0) === $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-due-date" class="form-label">Due Date</label>
                        <select id="filter-due-date" name="due_date" class="form-select">
                            <option value="">All Dates</option>
                            <option value="today" @selected(($filters['due_date'] ?? '') === 'today')>Today</option>
                            <option value="this_week" @selected(($filters['due_date'] ?? '') === 'this_week')>This Week</option>
                            <option value="this_month" @selected(($filters['due_date'] ?? '') === 'this_month')>This Month</option>
                            <option value="future" @selected(($filters['due_date'] ?? '') === 'future')>Future</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-project" class="form-label">Project</label>
                        <select id="filter-project" name="project_id" class="form-select">
                            <option value="">All Projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') === (string) $project->id)>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>Apply
                        </button>

                        @if (array_filter($filters))
                            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Tasks</h3>
        </div>

        @if ($tasks->count())
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Responsible</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                            <th scope="col">Due Date</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('tasks.edit', $task) }}" class="fw-semibold text-decoration-none">
                                        {{ $task->title }}
                                    </a>

                                    @if ($task->project)
                                        <div class="small text-body-secondary">{{ $task->project->name }}</div>
                                    @endif

                                    @if ($task->taskTransfers->isNotEmpty())
                                        <x-badge variant="info" icon="arrow-left-right">Transferred</x-badge>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->responsibleUser)
                                        <x-user-cell :name="$task->responsibleUser->name" :email="$task->responsibleUser->email" :size="32" />
                                    @else
                                        <span class="text-body-secondary">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <x-badge :variant="\App\Support\StatusBadge::priorityVariant($task->priority)">
                                        {{ \App\Support\StatusBadge::label($task->priority) }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :variant="\App\Support\StatusBadge::variant($task->status)">
                                        {{ \App\Support\StatusBadge::label($task->status) }}
                                    </x-badge>
                                </td>
                                <td class="text-nowrap">{{ $task->due_date->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('tasks.show', $task)" icon="eye" label="Details" />
                                        <x-icon-btn :href="route('task-transfers.index', ['task_id' => $task->id])" icon="arrow-left-right" label="Transfer" />
                                        <x-icon-btn :href="route('tasks.edit', $task)" icon="pencil" label="Edit" />

                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Are you sure you want to delete this task? This action cannot be undone."
                                                    data-confirm-button="Delete" aria-label="Delete" title="Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($tasks->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$tasks" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="inbox" title="No tasks found" description="Get started by creating a new task.">
                    <x-btn :href="route('tasks.create')" icon="plus-lg" size="sm">New Task</x-btn>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
