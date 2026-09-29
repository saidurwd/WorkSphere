<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-start mb-0">
                    @hasSection('breadcrumb')
                        <li class="breadcrumb-item">@yield('breadcrumb')</li>
                    @else
                        <li class="breadcrumb-item active">@yield('title')</li>
                    @endif
                </ol>
            </div>

            <div class="col-sm-6">
                <div class="float-sm-end">
                    @yield('header-actions')
                </div>
            </div>
        </div>
    </div>
</div>
