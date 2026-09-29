{{--
    See resources/views/vendor/tyro-dashboard/layouts/admin.blade.php — kept so
    package views extending `layouts.app` also land in the AdminLTE shell.
--}}
@extends('layouts.app')

@push('scripts')
    @include('tyro-dashboard::partials.scripts')
@endpush
