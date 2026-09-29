@extends('layouts.app')

@section('title', 'Login History')

@section('content')
    <x-page-header title="Login History" subtitle="Authentication attempts and session events." icon="clock-history" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.login-logs.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="event-filter" class="form-label">Event</label>
                    <select id="event-filter" name="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label for="user-filter" class="form-label">User ID</label>
                    <input id="user-filter" type="number" name="user_id" class="form-control" placeholder="User ID" value="{{ request('user_id') }}">
                </div>

                <div class="col-6 col-md-2">
                    <label for="date-from" class="form-label">From</label>
                    <input id="date-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="col-6 col-md-2">
                    <label for="date-to" class="form-label">To</label>
                    <input id="date-to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    @if (request()->hasAny(['event', 'user_id', 'date_from', 'date_to']))
                        <a href="{{ route('admin.login-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Login History</h3>
        </div>

        <div class="card-body p-0">
            <x-datatable id="login-logs-table" :options="['pageLength' => 30, 'order' => [[0, 'desc']]]">
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">User</th>
                        <th scope="col">Event</th>
                        <th scope="col">Device</th>
                        <th scope="col">IP Address</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->attempted_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if ($log->user)
                                    <x-user-cell :name="$log->user->name" :email="$log->user->email" :size="28" />
                                @else
                                    <span class="text-body-secondary">{{ $log->email }}</span>
                                @endif
                            </td>
                            <td>
                                <x-badge :variant="$log->eventVariant()">{{ $log->eventLabel() }}</x-badge>
                            </td>
                            <td>{{ $log->device ?? '—' }}</td>
                            <td class="text-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <x-icon-btn :href="route('admin.login-logs.show', $log)" icon="eye" label="View" />
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
