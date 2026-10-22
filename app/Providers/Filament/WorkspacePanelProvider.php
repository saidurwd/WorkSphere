<?php

namespace App\Providers\Filament;

use App\Filament\Workspace\Pages\WorkspaceDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class WorkspacePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('workspace')
            ->path('workspace')
            ->login()
            ->brandName('WorkSphere')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->discoverResources(in: base_path('Modules/Todos/app/Filament/Resources'), for: 'Modules\Todos\Filament\Resources')
            ->discoverResources(in: base_path('Modules/Tasks/app/Filament/Resources'), for: 'Modules\Tasks\Filament\Resources')
            ->discoverResources(in: base_path('Modules/Projects/app/Filament/Resources'), for: 'Modules\Projects\Filament\Resources')
            ->discoverResources(in: base_path('Modules/Obligations/app/Filament/Resources'), for: 'Modules\Obligations\Filament\Resources')
            ->discoverResources(in: base_path('Modules/Meetings/app/Filament/Resources'), for: 'Modules\Meetings\Filament\Resources')

            ->discoverPages(in: base_path('Modules/Todos/app/Filament/Pages'), for: 'Modules\\Todos\\Filament\\Pages')
            ->discoverPages(in: base_path('Modules/Tasks/app/Filament/Pages'), for: 'Modules\\Tasks\\Filament\\Pages')
            ->discoverPages(in: base_path('Modules/Obligations/app/Filament/Pages'), for: 'Modules\\Obligations\\Filament\\Pages')
            ->discoverPages(in: base_path('Modules/Meetings/app/Filament/Pages'), for: 'Modules\\Meetings\\Filament\\Pages')
            ->pages([
                WorkspaceDashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
