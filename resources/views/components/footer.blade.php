<footer class="app-footer">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6 text-sm-start text-center">
                <span class="text-sm">
                    &copy; {{ now()->year }}
                    <a href="{{ route('dashboard.index') }}" class="text-decoration-none">{{ config('app.name') }}</a>
                </span>
            </div>
            <div class="col-sm-6 text-sm-end text-center mt-2 mt-sm-0">
                <span class="text-sm">All rights reserved.</span>
            </div>
        </div>
    </div>
</footer>
