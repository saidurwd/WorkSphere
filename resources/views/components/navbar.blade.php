@php
    $user = auth()->user();
    $initials = strtoupper(substr($user?->name ?? 'U', 0, 2));

    $recentNotifications = collect();

    if ($user) {
        $obligationNotifications = \App\Models\NotificationLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'subject', 'status', 'created_at', 'notification_type as type']);

        $taskNotifications = \App\Models\TaskNotificationLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'subject', 'status', 'created_at', 'notification_type as type']);

        $meetingNotifications = \App\Models\MeetingNotificationLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'subject', 'status', 'created_at', 'notification_type as type']);

        $recentNotifications = $obligationNotifications
            ->merge($taskNotifications)
            ->merge($meetingNotifications)
            ->sortByDesc('created_at')
            ->take(8);
    }

    $unreadCount = $recentNotifications->count();
@endphp

<nav class="app-header navbar navbar-expand-md navbar-light bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </a>
            </li>

            <li class="nav-item d-none d-md-block">
                <a href="{{ route('dashboard.index') }}" class="nav-link">
                    <i class="bi bi-house-door me-1"></i>{{ config('app.name', 'Laravel') }}
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center">
            @auth
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" data-bs-toggle="dropdown" href="#" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if($unreadCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg p-0" style="width: 380px; max-height: 420px; overflow: hidden;">
                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Notifications</span>
                            <a href="{{ route('obligations.notifications') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View All</a>
                        </div>

                        <div class="overflow-auto" style="max-height: 320px;">
                            @forelse($recentNotifications as $notification)
                                @php
                                    $timeAgo = $notification->created_at->diffForHumans();
                                    $icon = match(true) {
                                        str_contains($notification->type, 'task') => 'bi-check2-square',
                                        str_contains($notification->type, 'meeting') => 'bi-calendar-week',
                                        str_contains($notification->type, 'obligation') => 'bi-file-earmark-text',
                                        default => 'bi-bell',
                                    };
                                    $iconBg = match(true) {
                                        str_contains($notification->type, 'task') => 'bg-primary',
                                        str_contains($notification->type, 'meeting') => 'bg-success',
                                        str_contains($notification->type, 'obligation') => 'bg-warning',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <a href="{{ route('obligations.notifications') }}" class="dropdown-item d-flex align-items-start gap-3 py-2 border-bottom text-decoration-none text-body">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="rounded-circle {{ $iconBg }} d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="bi {{ $icon }} text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="mb-1 text-truncate fw-medium">{{ $notification->subject }}</p>
                                        <small class="text-body-secondary">{{ $timeAgo }}</small>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-body-secondary mt-2 mb-0">No notifications yet</p>
                                </div>
                            @endforelse
                        </div>

                        @if($recentNotifications->count() > 0)
                            <div class="px-3 py-2 border-top text-center">
                                <a href="{{ route('obligations.notifications') }}" class="btn btn-sm btn-primary w-100">View All Notifications</a>
                            </div>
                        @endif
                    </div>
                </li>
            @endauth

            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="fullscreen" href="#" role="button" aria-label="Toggle fullscreen">
                    <i class="bi bi-fullscreen"></i>
                </a>
            </li>

            <li class="nav-item">
                <x-theme-toggle />
            </li>

            @auth
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <span class="user-image">{{ $initials }}</span>
                        <span class="d-none d-sm-inline">{{ $user?->name }}</span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">
                            <span class="d-block fw-semibold">{{ $user?->name }}</span>
                            <small class="text-body-secondary">{{ $user?->email }}</small>
                        </div>

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item" href="{{ route('dashboard.index') }}">
                            <i class="bi bi-person me-2"></i>Profile
                        </a>

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </li>
            @endauth
        </ul>
    </div>
</nav>
