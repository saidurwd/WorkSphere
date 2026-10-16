<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ request()->cookie('theme', 'light') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/search-box.js'])
    @stack('styles')
</head>

<body class="layout-fluid bg-body-tertiary">

<a href="#main-content" class="skip-link">Skip to main content</a>

<div class="app-wrapper">
    <x-sidebar />

    <x-navbar />

    <main class="app-main" id="main-content">
        <x-content-header :breadcrumbs="$breadcrumbs ?? null" />

        <div class="app-content">
            <div class="container-fluid">
                <x-flash />

                @yield('content')
            </div>
        </div>
    </main>

    <x-footer />
</div>

<x-confirm-dialog />

@if (app()->environment('local'))
    <script>
        (function () {
            function go() {
                try {
                    var el = function (s) { return document.querySelector(s); };
                    var rect = function (e) { if (!e) return null; var r = e.getBoundingClientRect(); return { l: Math.round(r.left), t: Math.round(r.top), w: Math.round(r.width), h: Math.round(r.height) }; };
                    var out = {
                        bodyClass: document.body.className,
                        sidebar: rect(el('.app-sidebar')),
                        header: rect(el('.app-header')),
                        main: rect(el('.app-main')),
                        navbarNav: rect(el('.app-sidebar .navbar-nav')),
                        box: rect(el('.small-box')),
                        sidebarCss: (function () { var s = el('.app-sidebar'); return s ? { width: getComputedStyle(s).width, position: getComputedStyle(s).position, transform: getComputedStyle(s).transform } : null; })(),
                        mainMarginLeft: (function () { var m = el('.app-main'); return m ? getComputedStyle(m).marginLeft : null; })(),
                        boxBgs: (function () { var r = []; document.querySelectorAll('.small-box').forEach(function (n) { r.push(getComputedStyle(n).backgroundColor); }); return r.slice(0, 5); })(),
                        varSidebarWidth: getComputedStyle(document.documentElement).getPropertyValue('--app-sidebar-width'),
                        docW: document.documentElement.clientWidth
                    };
                    document.title = '__PROBE__' + JSON.stringify(out);
                } catch (e) {
                    document.title = '__PROBE_ERR__' + (e && e.message);
                }
            }
            if (document.readyState !== 'loading') { setTimeout(go, 0); } else { document.addEventListener('DOMContentLoaded', function () { setTimeout(go, 0); }); }
            window.addEventListener('load', go);
        })();
    </script>
@endif

@stack('scripts')
</body>
</html>
