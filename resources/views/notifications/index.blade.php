@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <x-page-header title="Notifications"
                   subtitle="{{ $unreadCount }} unread"
                   icon="bell" />

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">Recent</h3>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="card-body">
                <x-empty-state icon="bell-slash" title="No notifications"
                               description="Anything assigned to you, or mentioning you, appears here." />
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($notifications as $notification)
                    @php $presented = \App\Support\NotificationPresenter::present($notification); @endphp
                    <li class="list-group-item d-flex align-items-start gap-3">
                        <div class="flex-shrink-0 mt-1">
                            <div class="rounded-circle {{ $presented['background'] }} d-flex align-items-center justify-content-center"
                                 style="width: 36px; height: 36px;">
                                <i class="bi {{ $presented['icon'] }} text-white"></i>
                            </div>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            {{-- The payload comes from the notifications table, so it is
                                 escaped rather than treated as markup. --}}
                            <p class="mb-1 {{ $presented['unread'] ? 'fw-semibold' : '' }}">
                                {{ $presented['title'] }}
                            </p>
                            <small class="text-body-secondary">{{ $presented['timeAgo'] }}</small>
                        </div>

                        <div class="d-flex gap-1">
                            <x-icon-btn :href="$presented['url']" icon="box-arrow-up-right" label="Open" />

                            @if ($presented['unread'])
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            aria-label="Mark as read: {{ $presented['title'] }}">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($notifications->hasPages())
                <div class="card-footer"><x-pagination :paginator="$notifications" /></div>
            @endif
        @endif
    </div>
@endsection
