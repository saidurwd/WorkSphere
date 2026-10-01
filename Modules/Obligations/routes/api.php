<?php

use Illuminate\Support\Facades\Route;
use Modules\Obligations\Http\Controllers\Api\ObligationApiController;

/*
|--------------------------------------------------------------------------
| Obligation API routes — /api/v1
|--------------------------------------------------------------------------
|
| Read only; approval and renewal writes run through the web controllers.
|
*/

Route::prefix('obligations')->name('obligations.')->group(function (): void {
    Route::get('/', [ObligationApiController::class, 'index'])->name('index');
    Route::get('{obligation}', [ObligationApiController::class, 'show'])->name('show');
});
