<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use Modules\Meetings\Http\Controllers\MeetingActionItemController;
use Modules\Meetings\Http\Controllers\MeetingAgendaController;
use Modules\Meetings\Http\Controllers\MeetingAttachmentController;
use Modules\Meetings\Http\Controllers\MeetingCalendarController;
use Modules\Meetings\Http\Controllers\MeetingController;
use Modules\Meetings\Http\Controllers\MeetingDashboardController;
use Modules\Meetings\Http\Controllers\MeetingDecisionController;
use Modules\Meetings\Http\Controllers\MeetingMinutesController;
use Modules\Meetings\Http\Controllers\MeetingNotificationLogController;
use Modules\Meetings\Http\Controllers\MeetingParticipantController;
use Modules\Meetings\Http\Controllers\MeetingReportController;
use Modules\Meetings\Http\Controllers\MeetingTagController;
use Modules\Meetings\Http\Controllers\MeetingTypeController;
use Modules\Obligations\Http\Controllers\ObligationCalendarController;
use Modules\Obligations\Http\Controllers\ObligationController;
use Modules\Obligations\Http\Controllers\ObligationDashboardController;
use Modules\Obligations\Http\Controllers\ObligationDocumentController;
use Modules\Obligations\Http\Controllers\ObligationDocumentListController;
use Modules\Obligations\Http\Controllers\ObligationMyTaskController;
use Modules\Obligations\Http\Controllers\ObligationNotificationController;
use Modules\Obligations\Http\Controllers\ObligationRenewalController;
use Modules\Obligations\Http\Controllers\ObligationRenewalListController;
use Modules\Obligations\Http\Controllers\ObligationReportController;
use Modules\Obligations\Http\Controllers\ObligationVendorController;
use Modules\Projects\Http\Controllers\ProjectController;
use Modules\Tasks\Http\Controllers\TaskController;
use Modules\Tasks\Http\Controllers\TaskNotificationLogController;
use Modules\Tasks\Http\Controllers\TaskTransferController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['web', 'auth'])->post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');





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

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
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

