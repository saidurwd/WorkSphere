@php
    $user = auth()->user();
@endphp

<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
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
            <x-theme-toggle />

            @auth
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                        <span class="user-cell-avatar">
                            {{ strtoupper(substr($user?->name ?? 'U', 0, 2)) }}
                        </span>
                        <span class="d-none d-sm-inline">{{ $user?->name }}</span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('dashboard.index') }}">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Sign out
                            </button>
                        </form>
                    </div>
                </li>
            @endauth
        </ul>
    </div>
</nav>
