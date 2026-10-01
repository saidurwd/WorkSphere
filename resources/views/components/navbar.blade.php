@php
    $user = auth()->user();
    $initials = strtoupper(substr($user?->name ?? 'U', 0, 2));

    $recentNotifications = collect();
    $unreadCount = 0;

    if ($user) {
        // GAP-021: one table, one query.
        //
        // This used to fan out across obligation_notification_logs,
        // task_notification_logs and meeting_notification_logs and merge the
        // results in PHP — three queries plus a merge on every page render, and
        // the "unread" count was really "how many rows are in the last 10 of
        // each of three logs", which double-counted and could not be marked read.
        //
        // `notifications` is Laravel's own table, so a notification is genuinely
        // read or genuinely unread, and marking one read is a single UPDATE.
        //
        // unreadNotifications() is the "unread" half and the listing is one
        // paginated query: counting and listing the same rows twice would put the
        // cost straight back.
        $recentNotifications = $user->notifications()
            ->where('type', \App\Support\NotificationPresenter::NOTIFICATION_CLASSES)
            ->latest()
            ->limit(8)
            ->get();

        $unreadCount = $user->unreadNotifications()
            ->where('type', \App\Support\NotificationPresenter::NOTIFICATION_CLASSES)
            ->count();
    }
@endphp

<nav class="app-header navbar navbar-expand-md bg-body">
    {{-- Three columns: left, centre search, right. `me-auto`/`ms-auto` are gone —
         the centre claims the free space with `flex-grow-1` and centres its own
         content, which is what puts the box in the middle rather than merely
         after the brand. --}}
    <div class="container-fluid d-flex align-items-center">
        <ul class="navbar-nav flex-shrink-0">
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

            {{-- Below `md` the centred box is hidden, so search would be
                 unreachable on a phone. This keeps it one tap away. --}}
            @auth
                <li class="nav-item d-md-none">
                    <a href="{{ route('search') }}" class="nav-link" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </a>
                </li>
            @endauth
        </ul>

        {{-- Global search box, centred. Type-ahead comes from a JSON endpoint and is
             rendered with textContent, never innerHTML: every title is user-authored
             text from five modules. Permissions are enforced server-side by the same
             service the full search page uses. --}}
        @auth
            <div class="navbar-search flex-grow-1 d-none d-md-flex justify-content-center px-3">
                <div class="position-relative w-100" style="max-width: 520px;" data-search-box
                     data-search-endpoint="{{ route('search.suggest') }}">
                    <form action="{{ route('search') }}" method="GET" role="search" class="d-flex">
                        <label for="navbar-search" class="visually-hidden">Search</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input id="navbar-search" type="search" name="q"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Search…" maxlength="120"
                                   autocomplete="off" role="combobox"
                                   aria-expanded="false" aria-controls="navbar-search-results"
                                   aria-autocomplete="list"
                                   aria-describedby="navbar-search-status"
                                   data-search-input>
                        </div>
                    </form>

                    {{-- aria-live so a screen reader hears the result count once a
                         debounced search finishes, not on every keystroke. --}}
                    <p id="navbar-search-status" class="visually-hidden" role="status" aria-live="polite"></p>

                    <div id="navbar-search-results"
                         class="dropdown-menu show p-0 shadow d-none"
                         style="width: min(92vw, 520px); max-height: 420px; overflow-y: auto; left: 0; top: 100%;"
                         role="listbox" aria-label="Search results"></div>
                </div>
            </div>
        @endauth

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
                            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View All</a>
                        </div>

                        <div class="overflow-auto" style="max-height: 320px;">
                            @forelse ($recentNotifications as $notification)
                                @php
                                    $presented = \App\Support\NotificationPresenter::present($notification);
                                @endphp
                                <a href="{{ $presented['url'] }}"
                                   class="dropdown-item d-flex align-items-start gap-3 py-2 border-bottom text-decoration-none text-body">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="rounded-circle {{ $presented['background'] }} d-flex align-items-center justify-content-center"
                                             style="width: 36px; height: 36px;">
                                            <i class="bi {{ $presented['icon'] }} text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="mb-1 text-truncate fw-medium">{{ $presented['title'] }}</p>
                                        <small class="text-body-secondary">{{ $presented['timeAgo'] }}</small>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-4">
                                    <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-body-secondary mt-2 mb-0">No notifications yet</p>
                                </div>
                            @endforelse
                        </div>

                        @if ($recentNotifications->isNotEmpty())
                            <div class="px-3 py-2 border-top text-center">
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                        Mark all as read
                                    </button>
                                </form>
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
                <li class="nav-item dropdown user-menu">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        @if($user?->avatar)
                            <img src="{{ asset('storage/'.$user->avatar) }}" class="user-image rounded-circle" alt="{{ $user->name }}" style="width: 2rem; height: 2rem; object-fit: cover;">
                        @else
                            <span class="user-image rounded-circle d-inline-flex align-items-center justify-content-center bg-secondary text-white" style="width: 2rem; height: 2rem; font-size: 0.75rem;">{{ $initials }}</span>
                        @endif
                        <span class="d-none d-sm-inline">{{ $user?->name }}</span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <a href="{{ route('dashboard.index') }}" class="user-header text-center text-decoration-none">
                            @if($user?->avatar)
                                <img src="{{ asset('storage/'.$user->avatar) }}" class="rounded-circle" alt="{{ $user->name }}" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid var(--bs-border-color-translucent);">
                            @else
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white" style="width: 90px; height: 90px; font-size: 2.25rem; border: 3px solid var(--bs-border-color-translucent);">
                                    {{ $initials }}
                                </div>
                            @endif
                            <p class="mt-2 mb-0 text-body">
                                {{ $user?->name }}
                                <small class="d-block text-body-secondary">{{ $user?->email }}</small>
                            </p>
                        </a>

                        <li class="user-body">
                            <div class="d-flex justify-content-between px-3 py-2">
                                <a href="{{ route('dashboard.index') }}" class="text-center text-decoration-none">
                                    <i class="bi bi-person d-block mb-1"></i>Profile
                                </a>
                                <a href="{{ route('notifications.index') }}" class="text-center text-decoration-none">
                                    <i class="bi bi-bell d-block mb-1"></i>Notifications
                                </a>
                            </div>
                        </li>

                        <li class="user-footer">
                            <form method="POST" action="{{ route('logout') }}" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @endauth
        </ul>
    </div>
</nav>
