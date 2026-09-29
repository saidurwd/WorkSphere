{{--
    The Tyro Dashboard screens (users, roles, privileges, resources, settings …)
    are still rendered by the package, but they now sit inside the AdminLTE
    shell. Delete this override together with the package once those screens
    are rebuilt natively.
--}}
@extends('layouts.app')

@push('scripts')
    {{--
        The package screens still call a handful of global helpers (modal
        open/close, clipboard, table filters). Their stylesheet counterpart
        lives in resources/css/package-screens.css.
    --}}
    @include('tyro-dashboard::partials.scripts')
@endpush
