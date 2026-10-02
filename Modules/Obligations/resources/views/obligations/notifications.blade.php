@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <x-page-header title="Notifications" subtitle="Notification logs for reminders and escalations." icon="bell" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('obligations.notifications') }}" method="GET" id="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="notification-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="notification-search" type="search" name="search" class="form-control"
                                   placeholder="Search notifications or obligations..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="status-filter" class="form-label">Status</label>
                        <select id="status-filter" name="status" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                            <option value="SENT" {{ request('status') === 'SENT' ? 'selected' : '' }}>Sent</option>
                            <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                            <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="channel-filter" class="form-label">Channel</label>
                        <select id="channel-filter" name="channel" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="IN_APP" {{ request('channel') === 'IN_APP' ? 'selected' : '' }}>In-App</option>
                            <option value="EMAIL" {{ request('channel') === 'EMAIL' ? 'selected' : '' }}>Email</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                        @if($notifications->count() > 0)
                            <button type="button" class="btn btn-danger" data-confirm="Delete all {{ $notifications->total() }} notifications? This cannot be undone." data-confirm-button="Delete all" data-confirm-submit="delete-all-form">
                                <i class="bi bi-trash3 me-1"></i>Delete All
                            </button>
                        @endif
                        @if(request()->hasAny(['search', 'status', 'channel']))
                            <a href="{{ route('obligations.notifications') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>

            <form id="delete-all-form" action="{{ route('obligations.notifications.destroy-all') }}" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
                @foreach($filters as $key => $value)
                    @if(!empty($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
            </form>
        </div>
    </div>

    <div class="card">
        @if($notifications->count())
            <div class="card-body p-0">
                <x-datatable id="notifications-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Obligation</th>
                            <th scope="col">Recipient</th>
                            <th scope="col">Channel</th>
                            <th scope="col">Type</th>
                            <th scope="col">Subject</th>
                            <th scope="col">Status</th>
                            <th scope="col">Scheduled At</th>
                            <th scope="col">Sent At</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifications as $notification)
                            <tr>
                                <td>
                                    @if($notification->obligation)
                                        <a href="{{ route('obligations.show', $notification->obligation) }}" class="fw-semibold text-decoration-none">
                                            {{ $notification->obligation->obligation_no }} - {{ $notification->obligation->title }}
                                        </a>
                                    @else
                                        <span class="text-body-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $notification->user->name ?? 'N/A' }}</td>
                                <td>
                                    <x-badge variant="secondary">{{ $notification->channel }}</x-badge>
                                </td>
                                <td>{{ $notification->notification_type }}</td>
                                <td>{{ $notification->subject }}</td>
                                <td>
                                    <x-badge :variant="match ($notification->status) {
                                        'SENT' => 'success',
                                        'FAILED' => 'danger',
                                        'CANCELLED' => 'secondary',
                                        default => 'warning',
                                    }">{{ $notification->status }}</x-badge>
                                </td>
                                <td>{{ $notification->scheduled_at ? $notification->scheduled_at->format('M d, Y H:i') : 'N/A' }}</td>
                                <td>{{ $notification->sent_at ? $notification->sent_at->format('M d, Y H:i') : 'N/A' }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <form action="{{ route('obligations.notifications.destroy', $notification) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete this notification?"
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

            @if ($notifications->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$notifications" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="bell" title="No notifications found" description="No notification logs available yet." />
            </div>
        @endif
    </div>
@endsection
