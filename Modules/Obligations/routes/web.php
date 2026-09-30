<?php

use Illuminate\Support\Facades\Route;
use Modules\Obligations\Http\Controllers\ObligationCalendarController;
use Modules\Obligations\Http\Controllers\ObligationController;
use Modules\Obligations\Http\Controllers\ObligationDashboardController;
use Modules\Obligations\Http\Controllers\ObligationDocumentController;
use Modules\Obligations\Http\Controllers\ObligationDocumentListController;
use Modules\Obligations\Http\Controllers\ObligationMyTaskController;
use Modules\Obligations\Http\Controllers\ObligationNotificationController;
use Modules\Obligations\Http\Controllers\ObligationRenewalController;
use Modules\Obligations\Http\Controllers\ObligationRenewalListController;
use Modules\Obligations\Http\Controllers\ObligationReportController;
use Modules\Obligations\Http\Controllers\ObligationVendorController;

/*
 * Routes owned by the Obligations module.
 *
 * Registered by Modules\Obligations\Providers\RouteServiceProvider with the
 * 'web' middleware group, which supplies session state and CSRF protection.
 */

Route::middleware(['web', 'auth'])->prefix('obligations')->name('obligations.')->group(function () {
    Route::get('/dashboard', [ObligationDashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/reports', [ObligationReportController::class, 'index'])->name('reports');
    Route::get('/calendar', [ObligationCalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [ObligationCalendarController::class, 'events'])->name('calendar.events');

    Route::get('/my-tasks', [ObligationMyTaskController::class, 'index'])->name('my-tasks');
    Route::get('/renewals', [ObligationRenewalListController::class, 'index'])->name('renewals');
    Route::get('/vendors', [ObligationVendorController::class, 'index'])->name('vendors');
    Route::get('/vendors/create', [ObligationVendorController::class, 'create'])->name('vendors.create');
    Route::post('/vendors', [ObligationVendorController::class, 'store'])->name('vendors.store');
    Route::get('/vendors/{vendor}/edit', [ObligationVendorController::class, 'edit'])->name('vendors.edit');
    Route::put('/vendors/{vendor}', [ObligationVendorController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [ObligationVendorController::class, 'destroy'])->name('vendors.destroy');
    Route::get('/documents', [ObligationDocumentListController::class, 'index'])->name('documents');
    Route::get('/notifications', [ObligationNotificationController::class, 'index'])->name('notifications');
    Route::delete('/notifications', [ObligationNotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [ObligationNotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/', [ObligationController::class, 'index'])->name('index');
    Route::get('/create', [ObligationController::class, 'create'])->name('create');
    Route::post('/', [ObligationController::class, 'store'])->name('store');
    Route::get('/{obligation}', [ObligationController::class, 'show'])->name('show');
    Route::get('/{obligation}/edit', [ObligationController::class, 'edit'])->name('edit');
    Route::put('/{obligation}', [ObligationController::class, 'update'])->name('update');
    Route::delete('/{obligation}', [ObligationController::class, 'destroy'])->name('destroy');

    Route::prefix('{obligation}/renew')->name('renew.')->group(function () {
        Route::get('/', [ObligationRenewalController::class, 'create'])->name('create');
        Route::post('/', [ObligationRenewalController::class, 'store'])->name('store');
    });

    Route::prefix('{obligation}/documents')->name('documents.')->group(function () {
        Route::post('/', [ObligationDocumentController::class, 'store'])->name('store');
        Route::delete('/{document}', [ObligationDocumentController::class, 'destroy'])->name('destroy');
    });
});

