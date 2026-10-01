<?php

use Illuminate\Support\Facades\Route;
use Modules\Todos\Http\Controllers\Api\TodoApiController;

/*
|--------------------------------------------------------------------------
| To-Do API routes — /api/v1
|--------------------------------------------------------------------------
|
| Mounted by the module's RouteServiceProvider under `api/v1`, with
| `auth:sanctum` and `throttle:api` already applied. Mutating routes add
| `throttle:api-writes` individually.
|
| Lifecycle segments are declared BEFORE `{todo}` so a literal path can never be
| captured as an id — the same ordering rule the web routes follow.
|
*/

Route::prefix('todos')->name('todos.')->group(function (): void {
    Route::get('/', [TodoApiController::class, 'index'])->name('index');
    Route::post('/', [TodoApiController::class, 'store'])
        ->middleware('throttle:api-writes')
        ->name('store');

    Route::get('{todo}/comments', [TodoApiController::class, 'comments'])->name('comments.index');
    Route::post('{todo}/comments', [TodoApiController::class, 'storeComment'])
        ->middleware('throttle:api-writes')
        ->name('comments.store');
    Route::get('{todo}/activity', [TodoApiController::class, 'activity'])->name('activity');

    Route::post('{todo}/complete', [TodoApiController::class, 'complete'])
        ->middleware('throttle:api-writes')
        ->name('complete');
    Route::post('{todo}/reopen', [TodoApiController::class, 'reopen'])
        ->middleware('throttle:api-writes')
        ->name('reopen');
    Route::post('{todo}/archive', [TodoApiController::class, 'archive'])
        ->middleware('throttle:api-writes')
        ->name('archive');
    Route::post('{todo}/restore', [TodoApiController::class, 'restore'])
        ->middleware('throttle:api-writes')
        ->name('restore');
    Route::post('{todo}/assign', [TodoApiController::class, 'assign'])
        ->middleware('throttle:api-writes')
        ->name('assign');

    Route::get('{todo}', [TodoApiController::class, 'show'])->name('show');
    Route::patch('{todo}', [TodoApiController::class, 'update'])
        ->middleware('throttle:api-writes')
        ->name('update');
    Route::delete('{todo}', [TodoApiController::class, 'destroy'])
        ->middleware('throttle:api-writes')
        ->name('destroy');
});
