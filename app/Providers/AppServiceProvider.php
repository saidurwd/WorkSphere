<?php

namespace App\Providers;

use App\Http\Controllers\EstateDivisionController;
use App\Http\Controllers\EstateStaffController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Route::middleware(['web', 'auth'])
            ->get('dashboard/resources/estate_staff', [EstateStaffController::class, 'index'])
            ->name('estate-staff.index');

        Route::middleware(['web', 'auth'])
            ->post('dashboard/resources/estate_staff', [EstateStaffController::class, 'store'])
            ->name('estate-staff.store');

        Route::middleware(['web', 'auth'])
            ->prefix('dashboard/resources/estate_staff')
            ->group(function () {
                Route::get('/create', [EstateStaffController::class, 'create'])->name('create');
                Route::get('/{id}', [EstateStaffController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [EstateStaffController::class, 'edit'])->name('edit');
                Route::put('/{id}', [EstateStaffController::class, 'update'])->name('update');
                Route::delete('/{id}', [EstateStaffController::class, 'destroy'])->name('destroy');
            });

        Route::middleware(['web', 'auth'])
            ->prefix('dashboard/resources/estate_divisions')
            ->group(function () {
                Route::get('/', [EstateDivisionController::class, 'index'])->name('index');
                Route::get('/create', [EstateDivisionController::class, 'create'])->name('create');
                Route::post('/', [EstateDivisionController::class, 'store'])->name('store');
                Route::get('/{id}', [EstateDivisionController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [EstateDivisionController::class, 'edit'])->name('edit');
                Route::put('/{id}', [EstateDivisionController::class, 'update'])->name('update');
                Route::delete('/{id}', [EstateDivisionController::class, 'destroy'])->name('destroy');
            });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(
            ['tyro-dashboard::resources.create', 'tyro-dashboard::resources.edit'],
            function ($view) {
                $data = $view->getData();

                if (($data['resource'] ?? null) === 'asset_assignments') {
                    $options = $data['options'] ?? [];

                    if (isset($options['asset_id'])) {
                        $selectedId = isset($data['item']) ? ($data['item']->asset_id ?? null) : null;

                        $options['asset_id'] = $options['asset_id']->filter(
                            fn ($asset) => ($asset->current_status ?? null) === 'In Stock'
                                || ($selectedId !== null && $asset->id === $selectedId)
                        );

                        $view->with('options', $options);
                    }
                }

                if (($data['resource'] ?? null) !== 'estate_staff') {
                    return;
                }

                $divisionsUrl = route('estate-staff.divisions');
                $selectedEstateId = isset($data['item']) ? ($data['item']->estate_id ?? null) : null;
                $selectedDivisionId = isset($data['item']) ? ($data['item']->division_id ?? null) : null;

                $view->with('estateStaffDivisionsUrl', $divisionsUrl);
                $view->with('estateStaffSelectedEstateId', $selectedEstateId);
                $view->with('estateStaffSelectedDivisionId', $selectedDivisionId);
            }
        );
    }
}
