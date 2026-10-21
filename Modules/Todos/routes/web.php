<?php

use Illuminate\Support\Facades\Route;
use Modules\Todos\Http\Controllers\CrossModuleLinkController;
use Modules\Todos\Http\Controllers\MyWorkController;

/*
|--------------------------------------------------------------------------
| To-Do routes
|--------------------------------------------------------------------------
|
| Static segments are declared BEFORE the `{todo}` parameter so `todos/inbox`
| cannot be matched as a To-Do whose id happens to be the string "inbox".
|
*/

Route::middleware(['web', 'auth'])->group(function (): void {
    // Outside the `todos.` prefix and outside `todos/`: this spans four modules,
    // so it is not a To-Do route and must not be reachable at /todos/my-work.
    Route::get('my-work', MyWorkController::class)->name('my-work');
});

/*
| One-click To-Do creation from another module, and the reverse navigation that
| answers "which To-Dos point at this record" (§7.2). Declared before the To-Do
| resource routes so `todos/links/...` cannot be captured as a todo id.
*/
Route::middleware(['web', 'auth'])->prefix('todos/links')->name('todos.links.')->group(function (): void {
    Route::get('reverse/{morphKey}/{id}', [CrossModuleLinkController::class, 'reverse'])->name('reverse');

    Route::post('from/action-item/{actionItem}', [CrossModuleLinkController::class, 'storeFromActionItem'])
        ->name('from.action-item');
    Route::post('from/obligation/{obligation}', [CrossModuleLinkController::class, 'storeFromObligation'])
        ->name('from.obligation');
    Route::post('from/task/{task}', [CrossModuleLinkController::class, 'storeFromTask'])
        ->name('from.task');
});
