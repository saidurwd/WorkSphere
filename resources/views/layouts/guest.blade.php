<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ request()->cookie('theme', 'light') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-body-tertiary">

<div class="d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-semibold mb-1">{{ config('app.name', 'Laravel') }}</h1>
                    <p class="text-body-secondary mb-0">@yield('subtitle')</p>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <x-flash />

                        @yield('content')
                    </div>
                </div>

                @hasSection('footer')
                    <p class="text-center text-body-secondary small mt-4 mb-0">@yield('footer')</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3">
    <x-theme-toggle />
</div>

@stack('scripts')
</body>

</html>
