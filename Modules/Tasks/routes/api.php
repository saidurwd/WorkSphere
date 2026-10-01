<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\Api\TaskApiController;

/*
|--------------------------------------------------------------------------
| Task API routes — /api/v1
|--------------------------------------------------------------------------
|
| Read only. Task writes run through TaskController and its service, which carry
| transfer, sub-task and time-entry side effects; an API write path would have to
| share that service rather than reimplement it, and that lands in v2.
|
*/

Route::prefix('tasks')->name('tasks.')->group(function (): void {
    Route::get('/', [TaskApiController::class, 'index'])->name('index');
    Route::get('{task}', [TaskApiController::class, 'show'])->name('show');
});
