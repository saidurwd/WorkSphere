@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
    <x-page-header title="Activity Logs" subtitle="Track user actions across the application." icon="journal-text" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="module-filter" class="form-label">Module</label>
                    <select id="module-filter" name="module" class="form-select">
                        <option value="">All Modules</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="action-filter" class="form-label">Action</label>
                    <select id="action-filter" name="action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="user-filter" class="form-label">User</label>
                    <input id="user-filter" type="number" name="user_id" class="form-control" placeholder="User ID" value="{{ request('user_id') }}">
                </div>

                <div class="col-6 col-md-3">
                    <label for="date-from" class="form-label">From</label>
                    <input id="date-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="col-6 col-md-3">
                    <label for="date-to" class="form-label">To</label>
                    <input id="date-to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    @if (request()->hasAny(['module', 'action', 'user_id', 'date_from', 'date_to']))
                        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Activity Logs</h3>
        </div>

        <div class="card-body p-0">
            <x-datatable id="activity-logs-table" :options="['pageLength' => 30, 'order' => [[0, 'desc']]]">
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">User</th>
                        <th scope="col">Module</th>
                        <th scope="col">Action</th>
                        <th scope="col">Record</th>
                        <th scope="col">IP Address</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if ($log->user)
                                    <x-user-cell :name="$log->user->name" :email="$log->user->email" :size="28" />
                                @else
                                    <span class="text-body-secondary">System</span>
                                @endif
                            </td>
                            <td>{{ $log->module_name ?? '—' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->record_id ?? '—' }}</td>
                            <td class="text-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <x-icon-btn :href="route('admin.activity-logs.show', $log)" icon="eye" label="View" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-datatable>
        </div>

        @if ($logs->hasPages())
            <div class="card-footer">
                <x-pagination :paginator="$logs" />
            </div>
        @endif
    </div>
@endsection
