@extends('layouts.app')

@section('title', 'To-Do Notification Log')

@section('content')
    <x-page-header title="Notification log" subtitle="Delivery history for To-Do notifications."
                   icon="bell" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('todos.notification-logs.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="log-type" class="form-label">Type</label>
                    <select id="log-type" name="type" class="form-select">
                        <option value="">All types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="log-status" class="form-label">Status</label>
                    <select id="log-status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach (['PENDING', 'SENT', 'FAILED'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Deliveries</h3></div>

        @if ($logs->isEmpty())
            <div class="card-body">
                <x-empty-state icon="bell-slash" title="No deliveries recorded"
                               description="Notifications appear here once the To-Do pipeline has run." />
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Type</th>
                            <th scope="col">Channel</th>
                            <th scope="col">To-Do</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($log->scheduled_at)->diffForHumans() }}</td>
                                <td>{{ $log->notification_type }}</td>
                                <td>{{ $log->channel }}</td>
                                <td>
                                    <a href="{{ route('todos.show', $log->subject_id) }}" class="text-decoration-none">
                                        #{{ $log->subject_id }}
                                    </a>
                                </td>
                                <td>
                                    @php $variant = match ($log->status) { 'SENT' => 'success', 'FAILED' => 'danger', default => 'secondary' }; @endphp
                                    <x-badge :variant="$variant">{{ $log->status }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="card-footer"><x-pagination :paginator="$logs" /></div>
            @endif
        @endif
    </div>
@endsection
