<?php

use App\Http\Controllers\Admin\System\HealthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchSuggestController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->middleware('throttle:60,1')->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:login')->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['web', 'auth'])->post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| Global search (GAP-035)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('search', SearchController::class)->middleware('throttle:search')->name('search');

    // Type-ahead for the navbar box. JSON, so the client never has to trust a
    // server-rendered fragment it would have to inject as HTML.
    Route::get('search/suggest', SearchSuggestController::class)->middleware('throttle:search')->name('search.suggest');
});

/*
|--------------------------------------------------------------------------
| Shared reports (GAP-036) and CSV export (GAP-050)
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Reference data administration (GAP-041)
|--------------------------------------------------------------------------
|
| employees, companies, departments and locations had models and no routes at
| all, so this data could only be changed with raw SQL — which is how it ends up
| frozen at whatever the seeder wrote. One controller serves all four from a
| descriptor, which is what keeps their validation from drifting apart.
|
*/

Route::middleware(['web', 'auth'])->prefix('reports')->name('reports.')->group(function (): void {
    Route::get('tasks', [ReportController::class, 'tasks'])->name('tasks');
    Route::get('tasks/export', [ReportController::class, 'exportCompletion'])->middleware('throttle:export')->name('tasks.export');
    Route::get('task-workload', [ReportController::class, 'taskWorkload'])->name('workload');
    Route::get('task-workload/export', [ReportController::class, 'exportWorkload'])->middleware('throttle:export')->name('workload.export');
    Route::get('task-distribution/export', [ReportController::class, 'exportDistribution'])->middleware('throttle:export')->name('distribution.export');
});

/*
|--------------------------------------------------------------------------
| Notification centre (GAP-021)
|--------------------------------------------------------------------------
|
| Reads the `notifications` table rather than the three per-module delivery
| logs. The logs record what the system *attempted*, including failures; this
| records what the user has *received*, and can therefore be marked read.
|
*/

Route::middleware(['web', 'auth'])->prefix('notifications')->name('notifications.')->group(function (): void {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
    Route::post('{notification}/read', [NotificationController::class, 'markRead'])->name('read');
});

Route::middleware(['web', 'auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // The dashboard is the workspace panel's home page. This name stays because the
    // sign-in redirect and the shared layout link to it.
    Route::get('/', fn () => redirect()->route('filament.workspace.pages.dashboard'))->name('index');

    Route::get('ui-kit', function () {
        abort_unless(app()->environment('local'), 404);

        return view('ui-kit.index');
    })->name('ui-kit');
});

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::get('database-backups', [DatabaseBackupController::class, 'index'])
            ->name('database-backups.index');
        Route::post('database-backups', [DatabaseBackupController::class, 'store'])
            ->name('database-backups.store');
        Route::get('database-backups/{filename}/download', [DatabaseBackupController::class, 'download'])
            ->name('database-backups.download');
        Route::delete('database-backups/{filename}', [DatabaseBackupController::class, 'destroy'])
            ->name('database-backups.destroy');
    });

/*
 * Machine-facing health probes. Outside every auth group because an orchestrator
 * cannot hold a session, and deliberately narrow: each returns a verdict and a few
 * facts, never a DSN, a credential or a row count.
 */
Route::get('livez', [HealthController::class, 'livez'])->name('livez');
Route::get('readyz', [HealthController::class, 'readyz'])->name('readyz');
