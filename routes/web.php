<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\ReferenceDataController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\System\FeatureFlagController;
use App\Http\Controllers\Admin\System\HealthController;
use App\Http\Controllers\Admin\System\QueueController;
use App\Http\Controllers\Admin\System\ScheduleController;
use App\Http\Controllers\Admin\System\SettingsController;
use App\Http\Controllers\Admin\System\TokenController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchSuggestController;
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

Route::middleware(['web', 'auth', 'admin', 'throttle:admin'])->prefix('admin/reference')->name('admin.reference.')->group(function (): void {
    Route::get('{resource}', [ReferenceDataController::class, 'index'])->name('index');
    Route::get('{resource}/create', [ReferenceDataController::class, 'create'])->name('create');
    Route::post('{resource}', [ReferenceDataController::class, 'store'])->name('store');
    Route::get('{resource}/{id}/edit', [ReferenceDataController::class, 'edit'])->name('edit');
    Route::put('{resource}/{id}', [ReferenceDataController::class, 'update'])->name('update');
    Route::delete('{resource}/{id}', [ReferenceDataController::class, 'destroy'])->name('destroy');
});

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
    Route::get('/', [DashboardController::class, '__invoke'])->name('index');

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

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // System administration. Each screen behind its OWN permission rather than
        // the blanket `system.manage`, so an operator who can read the health of
        // the system is not thereby also handed the ability to change how it
        // behaves. `system.manage` remains as the super-admin shortcut.
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('health', [HealthController::class, 'index'])->name('health.index');

            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

            Route::get('queue', [QueueController::class, 'index'])->name('queue.index');
            Route::post('queue/retry', [QueueController::class, 'retry'])->name('queue.retry');
            Route::post('queue/retry-all', [QueueController::class, 'retryAll'])->name('queue.retry-all');
            Route::post('queue/forget', [QueueController::class, 'forget'])->name('queue.forget');
            Route::post('queue/flush', [QueueController::class, 'flush'])->name('queue.flush');

            Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');

            Route::get('flags', [FeatureFlagController::class, 'index'])->name('flags.index');
            Route::post('flags', [FeatureFlagController::class, 'store'])->name('flags.store');
            Route::put('flags/{flag}', [FeatureFlagController::class, 'update'])->name('flags.update');
            Route::post('flags/{flag}/toggle', [FeatureFlagController::class, 'toggle'])->name('flags.toggle');
            Route::delete('flags/{flag}', [FeatureFlagController::class, 'destroy'])->name('flags.destroy');

            Route::get('tokens', [TokenController::class, 'index'])->name('tokens.index');
            Route::delete('tokens/{tokenId}', [TokenController::class, 'destroy'])->name('tokens.destroy');
            Route::post('tokens/revoke-others', [TokenController::class, 'destroyOthers'])->name('tokens.destroy-others');
        });

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

        Route::get('login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');
        Route::get('login-logs/{loginLog}', [LoginLogController::class, 'show'])->name('login-logs.show');

        Route::get('security-events', [SecurityEventController::class, 'index'])->name('security-events.index');
        Route::get('security-events/{securityEvent}', [SecurityEventController::class, 'show'])->name('security-events.show');
    });
