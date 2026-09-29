<footer class="app-footer">
    <div class="float-end d-none d-sm-inline">
        <a href="{{ route('dashboard.index') }}">{{ config('app.name', 'Laravel') }}</a>
    </div>

    <strong>
        &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
    </strong>
</footer>
