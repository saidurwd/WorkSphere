<?php

use Illuminate\Support\Facades\Route;
use Modules\Todos\Http\Controllers\CrossModuleLinkController;
use Modules\Todos\Http\Controllers\MyWorkController;
use Modules\Todos\Http\Controllers\TodoCalendarController;
use Modules\Todos\Http\Controllers\TodoChecklistController;
use Modules\Todos\Http\Controllers\TodoCommentController;
use Modules\Todos\Http\Controllers\TodoController;
use Modules\Todos\Http\Controllers\TodoLinkController;
use Modules\Todos\Http\Controllers\TodoNotificationLogController;
use Modules\Todos\Http\Controllers\TodoReportController;
use Modules\Todos\Http\Controllers\TodoWatcherController;

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

Route::middleware(['web', 'auth'])->prefix('todos')->name('todos.')->group(function (): void {
    Route::get('/', [TodoController::class, 'index'])->name('index');
    Route::get('inbox', [TodoController::class, 'inbox'])->name('inbox');
    Route::get('calendar', TodoCalendarController::class)->name('calendar');
    Route::get('reports', [TodoReportController::class, 'index'])->name('reports');
    Route::get('reports/export', [TodoReportController::class, 'export'])->name('reports.export');
    Route::get('notification-logs', [TodoNotificationLogController::class, 'index'])
        ->name('notification-logs.index');
    Route::delete('notification-logs/{log}', [TodoNotificationLogController::class, 'destroy'])
        ->name('notification-logs.destroy');
    Route::post('bulk', [TodoController::class, 'bulk'])->name('bulk');

    Route::get('create', [TodoController::class, 'create'])->name('create');
    Route::post('/', [TodoController::class, 'store'])->name('store');

    // Lifecycle. Declared before the `{todo}` show/edit pair so a literal path
    // segment can never be captured as an id.
    Route::post('{todo}/complete', [TodoController::class, 'complete'])->name('complete');
    Route::post('{todo}/reopen', [TodoController::class, 'reopen'])->name('reopen');
    Route::post('{todo}/archive', [TodoController::class, 'archive'])->name('archive');
    Route::post('{todo}/restore', [TodoController::class, 'restore'])->name('restore');
    Route::post('{todo}/assign', [TodoController::class, 'assign'])->name('assign');
    Route::post('{todo}/status', [TodoController::class, 'transition'])->name('status');

    Route::post('{todo}/checklist', [TodoChecklistController::class, 'store'])->name('checklist.store');
    Route::put('{todo}/checklist/{item}', [TodoChecklistController::class, 'update'])
        ->name('checklist.update');
    Route::delete('{todo}/checklist/{item}', [TodoChecklistController::class, 'destroy'])
        ->name('checklist.destroy');

    Route::post('{todo}/comments', [TodoCommentController::class, 'store'])->name('comments.store');

    Route::post('{todo}/watchers', [TodoWatcherController::class, 'store'])->name('watchers.store');
    Route::delete('{todo}/watchers/{user}', [TodoWatcherController::class, 'destroy'])
        ->name('watchers.destroy');

    Route::post('{todo}/links', [TodoLinkController::class, 'store'])->name('links.store');
    Route::delete('{todo}/links/{link}', [TodoLinkController::class, 'destroy'])->name('links.destroy');

    Route::get('{todo}', [TodoController::class, 'show'])->name('show');
    Route::get('{todo}/edit', [TodoController::class, 'edit'])->name('edit');
    Route::put('{todo}', [TodoController::class, 'update'])->name('update');
    Route::delete('{todo}', [TodoController::class, 'destroy'])->name('destroy');
});
