<?php

use Illuminate\Support\Facades\Route;
use Modules\Meetings\Http\Controllers\Api\MeetingApiController;

/*
|--------------------------------------------------------------------------
| Meeting API routes — /api/v1
|--------------------------------------------------------------------------
|
| Read only; the lifecycle runs through MeetingService on the web side.
|
*/

Route::prefix('meetings')->name('meetings.')->group(function (): void {
    Route::get('/', [MeetingApiController::class, 'index'])->name('index');
    Route::get('{meeting}', [MeetingApiController::class, 'show'])->name('show');
});
