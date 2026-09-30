<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\TaskController;
use Modules\Tasks\Http\Controllers\TaskNotificationLogController;
use Modules\Tasks\Http\Controllers\TaskTransferController;

/*
 * Routes owned by the Tasks module.
 *
 * Registered by Modules\Tasks\Providers\RouteServiceProvider with the
 * 'web' middleware group, which supplies session state and CSRF protection.
 */

Route::middleware(['web', 'auth'])->prefix('tasks')->name('tasks.')->group(function () {
    Route::get('/dashboard', [TaskController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::get('/create', [TaskController::class, 'create'])->name('create');
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::get('/notification-logs', [TaskNotificationLogController::class, 'index'])->name('notification-logs.index');
    Route::delete('/notification-logs', [TaskNotificationLogController::class, 'destroyAll'])->name('notification-logs.destroy-all');
    Route::delete('/notification-logs/{log}', [TaskNotificationLogController::class, 'destroy'])->name('notification-logs.destroy');
    Route::get('/{task}', [TaskController::class, 'show'])->name('show');
    Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
    Route::put('/{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
    Route::post('/{task}/remarks', [TaskController::class, 'storeRemark'])->name('remarks.store');
});

Route::middleware(['web', 'auth'])->prefix('task-transfers')->name('task-transfers.')->group(function () {
    Route::get('/', [TaskTransferController::class, 'index'])->name('index');
    Route::post('/', [TaskTransferController::class, 'store'])->name('store');
    Route::delete('/{taskTransfer}', [TaskTransferController::class, 'destroy'])->name('destroy');
});

