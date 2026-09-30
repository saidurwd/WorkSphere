@php
    $user = auth()->user();
    $initials = strtoupper(substr($user?->name ?? 'U', 0, 2));
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
