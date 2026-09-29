<footer class="app-footer">
    <div class="float-end d-none d-sm-inline">
        <a href="{{ route('dashboard.index') }}">{{ config('app.name') }}</a>
    </div>

    <strong>&copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}</strong>. All rights reserved.
</footer>
