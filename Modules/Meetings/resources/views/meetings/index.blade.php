@extends('layouts.app')

@section('title', 'Meetings')

@section('content')
    <x-page-header title="Meetings" subtitle="Schedule and manage meetings." icon="calendar-week">
        <x-btn :href="route('meetings.create')" icon="plus-lg">New Meeting</x-btn>
    </x-page-header>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('meetings.index') }}" method="GET" id="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="meeting-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="meeting-search" type="search" name="search" class="form-control"
                                   placeholder="Search meetings..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="status-filter" class="form-label">Status</label>
                        <select id="status-filter" name="status" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="postponed" {{ request('status') === 'postponed' ? 'selected' : '' }}>Postponed</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="type-filter" class="form-label">Type</label>
                        <select id="type-filter" name="meeting_type_id" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" {{ request('meeting_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="department-filter" class="form-label">Department</label>
                        <select id="department-filter" name="department_id" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="date-from" class="form-label">From</label>
                        <input id="date-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="date-to" class="form-label">To</label>
                        <input id="date-to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                        @if(request()->hasAny(['search', 'status', 'meeting_type_id', 'department_id', 'date_from', 'date_to']))
                            <a href="{{ route('meetings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if($meetings->count())
            <div class="card-body p-0">
                <x-datatable id="meetings-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Meeting No</th>
                            <th scope="col">Title</th>
                            <th scope="col">Date</th>
                            <th scope="col">Type</th>
                            <th scope="col">Department</th>
                            <th scope="col">Organizer</th>
                            <th scope="col">Status</th>
                            <th scope="col">Minutes</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meetings as $meeting)
                            <tr>
                                <td><span class="fw-semibold" style="font-family: monospace;">{{ $meeting->meeting_no }}</span></td>
                                <td>
                                    <a href="{{ route('meetings.show', $meeting) }}" class="fw-semibold text-decoration-none">
                                        {{ $meeting->title }}
                                    </a>
                                </td>
                                <td>{{ $meeting->meeting_date->format('M d, Y') }}</td>
                                <td>{{ $meeting->type->name ?? 'N/A' }}</td>
                                <td>{{ $meeting->department->department_name ?? 'N/A' }}</td>
                                <td>{{ $meeting->organizer->name ?? 'N/A' }}</td>
                                <td>
                                    <x-badge :variant="match ($meeting->status) {
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'in_progress' => 'primary',
                                        'postponed' => 'warning',
                                        default => 'secondary',
                                    }">{{ ucwords(str_replace('_', ' ', $meeting->status)) }}</x-badge>
                                </td>
                                <td>
                                    <x-badge :variant="match ($meeting->minutes_status) {
                                        'published' => 'success',
                                        'approved' => 'primary',
                                        'submitted' => 'warning',
                                        default => 'secondary',
                                    }">{{ ucwords(str_replace('_', ' ', $meeting->minutes_status)) }}</x-badge>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.show', $meeting)" icon="eye" label="View" />
                                        <x-icon-btn :href="route('meetings.edit', $meeting)" icon="pencil" label="Edit" />
                                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete meeting "{{ $meeting->title }}"? This cannot be undone."
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

            @if ($meetings->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$meetings" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="calendar-week" title="No meetings found" description="Get started by scheduling a new meeting.">
                    <x-btn :href="route('meetings.create')" icon="plus-lg" size="sm">New Meeting</x-btn>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
