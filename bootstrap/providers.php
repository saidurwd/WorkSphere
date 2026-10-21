<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\WorkspacePanelProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    AdminPanelProvider::class,
    WorkspacePanelProvider::class,
];
