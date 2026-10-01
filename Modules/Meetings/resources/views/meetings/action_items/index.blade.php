@extends('layouts.app')

@section('title', 'Action Items')

@section('content')
    <x-page-header title="Action Items" subtitle="Track meeting action items and tasks." icon="list-check" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('meetings.action-items.index') }}" method="GET" id="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="action-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="action-search" type="search" name="search" class="form-control"
                                   placeholder="Search action items..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="status-filter" class="form-label">Status</label>
                        <select id="status-filter" name="status" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="priority-filter" class="form-label">Priority</label>
                        <select id="priority-filter" name="priority" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                        @if(request()->hasAny(['search', 'status', 'priority', 'meeting_id', 'overdue']))
                            <a href="{{ route('meetings.action-items.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if($actionItems->count())
            <div class="card-body p-0">
                <x-datatable id="action-items-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Action</th>
                            <th scope="col">Meeting</th>
                            <th scope="col">Assigned To</th>
                            <th scope="col">Department</th>
                            <th scope="col">Due Date</th>
                            <th scope="col">Status</th>
                            <th scope="col">Task</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($actionItems as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('meetings.action-items.show', $item) }}" class="fw-semibold text-decoration-none">
                                        {{ $item->title }}
                                    </a>
                                </td>
                                <td>{{ $item->meeting->title ?? 'N/A' }}</td>
                                <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                                <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                                <td>{{ $item->due_date ? $item->due_date->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    <x-badge :variant="\App\Support\StatusBadge::variant($item->status)">
                                    @if($item->isOverdue())
                                        <x-badge variant="danger">Overdue</x-badge>
                                    @endif
                                </td>
                                <td>
                                    @if($item->task)
                                        <a href="{{ route('tasks.show', $item->task) }}" class="text-decoration-none">{{ $item->task->task_no ?? 'Task #'.$item->task->id }}</a>
                                    @else
                                        <span class="text-body-secondary">Not linked</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable>
            </div>

            @if ($actionItems->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$actionItems" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="list-check" title="No action items found" description="Action items will appear here when created." />
            </div>
        @endif
    </div>
@endsection
