<?php

use App\Http\Controllers\Admin\System\HealthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\ReportController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/workspace');

// Sign-in lives in the Filament panels (one page, App\Filament\Auth\Login). The
// name stays because the `auth` middleware redirects guests to `route('login')`.
Route::get('login', fn () => redirect('/workspace/login'))->name('login');

Route::middleware(['web', 'auth'])->post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| Global search (GAP-035)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function (): void {

    // Type-ahead for the navbar box. JSON, so the client never has to trust a
    // server-rendered fragment it would have to inject as HTML.
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
    Route::get('tasks/export', [ReportController::class, 'exportCompletion'])->middleware('throttle:export')->name('tasks.export');
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

Route::middleware(['web', 'auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // The dashboard is the workspace panel's home page. This name stays because the
    // sign-in redirect and the shared layout link to it.
    Route::get('/', fn () => redirect()->route('filament.workspace.pages.dashboard'))->name('index');

});

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        // Listing, creating and deleting backups is the Database backups page in the
        // admin panel; only the file download is a plain route.
        Route::get('database-backups/{filename}/download', [DatabaseBackupController::class, 'download'])
            ->name('database-backups.download');
    });

/*
 * Machine-facing health probes. Outside every auth group because an orchestrator
 * cannot hold a session, and deliberately narrow: each returns a verdict and a few
 * facts, never a DSN, a credential or a row count.
 */
Route::get('livez', [HealthController::class, 'livez'])->name('livez');
Route::get('readyz', [HealthController::class, 'readyz'])->name('readyz');
