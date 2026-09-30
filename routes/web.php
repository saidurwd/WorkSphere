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
use App\Http\Controllers\MeetingActionItemController;
use App\Http\Controllers\MeetingAgendaController;
use App\Http\Controllers\MeetingAttachmentController;
use App\Http\Controllers\MeetingCalendarController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingDashboardController;
use App\Http\Controllers\MeetingDecisionController;
use App\Http\Controllers\MeetingMinutesController;
use App\Http\Controllers\MeetingNotificationLogController;
use App\Http\Controllers\MeetingParticipantController;
use App\Http\Controllers\MeetingReportController;
use App\Http\Controllers\MeetingTagController;
use App\Http\Controllers\MeetingTypeController;
use App\Http\Controllers\ObligationCalendarController;
use App\Http\Controllers\ObligationController;
use App\Http\Controllers\ObligationDashboardController;
use App\Http\Controllers\ObligationDocumentController;
use App\Http\Controllers\ObligationDocumentListController;
use App\Http\Controllers\ObligationMyTaskController;
use App\Http\Controllers\ObligationNotificationController;
use App\Http\Controllers\ObligationRenewalController;
use App\Http\Controllers\ObligationRenewalListController;
use App\Http\Controllers\ObligationReportController;
use App\Http\Controllers\ObligationVendorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskNotificationLogController;
use App\Http\Controllers\TaskTransferController;
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

Route::middleware(['web', 'auth'])->prefix('obligations')->name('obligations.')->group(function () {
    Route::get('/dashboard', [ObligationDashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/reports', [ObligationReportController::class, 'index'])->name('reports');
    Route::get('/calendar', [ObligationCalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [ObligationCalendarController::class, 'events'])->name('calendar.events');

    Route::get('/my-tasks', [ObligationMyTaskController::class, 'index'])->name('my-tasks');
    Route::get('/renewals', [ObligationRenewalListController::class, 'index'])->name('renewals');
    Route::get('/vendors', [ObligationVendorController::class, 'index'])->name('vendors');
    Route::get('/vendors/create', [ObligationVendorController::class, 'create'])->name('vendors.create');
    Route::post('/vendors', [ObligationVendorController::class, 'store'])->name('vendors.store');
    Route::get('/vendors/{vendor}/edit', [ObligationVendorController::class, 'edit'])->name('vendors.edit');
    Route::put('/vendors/{vendor}', [ObligationVendorController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [ObligationVendorController::class, 'destroy'])->name('vendors.destroy');
    Route::get('/documents', [ObligationDocumentListController::class, 'index'])->name('documents');
    Route::get('/notifications', [ObligationNotificationController::class, 'index'])->name('notifications');
    Route::delete('/notifications', [ObligationNotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [ObligationNotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/', [ObligationController::class, 'index'])->name('index');
    Route::get('/create', [ObligationController::class, 'create'])->name('create');
    Route::post('/', [ObligationController::class, 'store'])->name('store');
    Route::get('/{obligation}', [ObligationController::class, 'show'])->name('show');
    Route::get('/{obligation}/edit', [ObligationController::class, 'edit'])->name('edit');
    Route::put('/{obligation}', [ObligationController::class, 'update'])->name('update');
    Route::delete('/{obligation}', [ObligationController::class, 'destroy'])->name('destroy');

    Route::prefix('{obligation}/renew')->name('renew.')->group(function () {
        Route::get('/', [ObligationRenewalController::class, 'create'])->name('create');
        Route::post('/', [ObligationRenewalController::class, 'store'])->name('store');
    });

    Route::prefix('{obligation}/documents')->name('documents.')->group(function () {
        Route::post('/', [ObligationDocumentController::class, 'store'])->name('store');
        Route::delete('/{document}', [ObligationDocumentController::class, 'destroy'])->name('destroy');
    });
});

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

Route::middleware(['web', 'auth'])->prefix('projects')->name('projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
    Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
    Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'auth'])->prefix('task-transfers')->name('task-transfers.')->group(function () {
    Route::get('/', [TaskTransferController::class, 'index'])->name('index');
    Route::post('/', [TaskTransferController::class, 'store'])->name('store');
    Route::delete('/{taskTransfer}', [TaskTransferController::class, 'destroy'])->name('destroy');
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

Route::middleware(['web', 'auth'])->prefix('meetings')->name('meetings.')->group(function () {
    Route::get('/dashboard', [MeetingDashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/calendar', [MeetingCalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [MeetingCalendarController::class, 'events'])->name('calendar.events');

    Route::get('/action-items', [MeetingActionItemController::class, 'index'])->name('action-items.index');
    Route::get('/action-items/{actionItem}', [MeetingActionItemController::class, 'show'])->name('action-items.show');

    Route::get('/reports', [MeetingReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/meetings', [MeetingReportController::class, 'meetings'])->name('reports.meetings');
    Route::get('/reports/actions', [MeetingReportController::class, 'actions'])->name('reports.actions');
    Route::get('/reports/overdue', [MeetingReportController::class, 'overdueActions'])->name('reports.overdue');
    Route::get('/reports/person-wise', [MeetingReportController::class, 'personWise'])->name('reports.person-wise');
    Route::get('/reports/department-wise', [MeetingReportController::class, 'departmentWise'])->name('reports.department-wise');
    Route::get('/reports/decisions', [MeetingReportController::class, 'decisions'])->name('reports.decisions');

    Route::get('/types', [MeetingTypeController::class, 'index'])->name('types.index');
    Route::get('/types/create', [MeetingTypeController::class, 'create'])->name('types.create');
    Route::post('/types', [MeetingTypeController::class, 'store'])->name('types.store');
    Route::get('/types/{meetingType}/edit', [MeetingTypeController::class, 'edit'])->name('types.edit');
    Route::put('/types/{meetingType}', [MeetingTypeController::class, 'update'])->name('types.update');
    Route::delete('/types/{meetingType}', [MeetingTypeController::class, 'destroy'])->name('types.destroy');
    Route::get('/tags', [MeetingTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/create', [MeetingTagController::class, 'create'])->name('tags.create');
    Route::post('/tags', [MeetingTagController::class, 'store'])->name('tags.store');
    Route::get('/tags/{meetingTag}/edit', [MeetingTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{meetingTag}', [MeetingTagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{meetingTag}', [MeetingTagController::class, 'destroy'])->name('tags.destroy');

    Route::get('/', [MeetingController::class, 'index'])->name('index');
    Route::get('/create', [MeetingController::class, 'create'])->name('create');
    Route::post('/', [MeetingController::class, 'store'])->name('store');
    Route::get('/notification-logs', [MeetingNotificationLogController::class, 'index'])->name('notification-logs.index');
    Route::delete('/notification-logs', [MeetingNotificationLogController::class, 'destroyAll'])->name('notification-logs.destroy-all');
    Route::delete('/notification-logs/{log}', [MeetingNotificationLogController::class, 'destroy'])->name('notification-logs.destroy');
    Route::get('/{meeting}', [MeetingController::class, 'show'])->name('show');
    Route::get('/{meeting}/print', [MeetingController::class, 'print'])->name('print');
    Route::get('/{meeting}/edit', [MeetingController::class, 'edit'])->name('edit');
    Route::put('/{meeting}', [MeetingController::class, 'update'])->name('update');
    Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->name('destroy');
    Route::post('/{meeting}/start', [MeetingController::class, 'start'])->name('start');
    Route::post('/{meeting}/complete', [MeetingController::class, 'complete'])->name('complete');
    Route::post('/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('cancel');

    Route::prefix('{meeting}/minutes')->name('minutes.')->group(function () {
        Route::post('/prepare', [MeetingMinutesController::class, 'prepare'])->name('prepare');
        Route::post('/submit', [MeetingMinutesController::class, 'submit'])->name('submit');
        Route::post('/approve', [MeetingMinutesController::class, 'approve'])->name('approve');
        Route::post('/publish', [MeetingMinutesController::class, 'publish'])->name('publish');
        Route::post('/return', [MeetingMinutesController::class, 'returnMinutes'])->name('return');
    });

    Route::prefix('{meeting}/agendas')->name('agendas.')->group(function () {
        Route::get('/', [MeetingAgendaController::class, 'index'])->name('index');
        Route::get('/create', [MeetingAgendaController::class, 'create'])->name('create');
        Route::post('/', [MeetingAgendaController::class, 'store'])->name('store');
        Route::get('/{agenda}/edit', [MeetingAgendaController::class, 'edit'])->name('edit');
        Route::put('/{agenda}', [MeetingAgendaController::class, 'update'])->name('update');
        Route::delete('/{agenda}', [MeetingAgendaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('{meeting}/decisions')->name('decisions.')->group(function () {
        Route::get('/', [MeetingDecisionController::class, 'index'])->name('index');
        Route::get('/create', [MeetingDecisionController::class, 'create'])->name('create');
        Route::post('/', [MeetingDecisionController::class, 'store'])->name('store');
        Route::get('/{decision}/edit', [MeetingDecisionController::class, 'edit'])->name('edit');
        Route::put('/{decision}', [MeetingDecisionController::class, 'update'])->name('update');
        Route::delete('/{decision}', [MeetingDecisionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('{meeting}/action-items')->name('action-items.')->group(function () {
        Route::post('/', [MeetingActionItemController::class, 'store'])->name('store');
        Route::put('/{actionItem}', [MeetingActionItemController::class, 'update'])->name('update');
        Route::delete('/{actionItem}', [MeetingActionItemController::class, 'destroy'])->name('destroy');

        Route::post('/{actionItem}/tasks', [MeetingActionItemController::class, 'storeTask'])->name('tasks.store');
        Route::post('/{actionItem}/tasks/link', [MeetingActionItemController::class, 'linkTask'])->name('tasks.link');
        Route::delete('/{actionItem}/tasks', [MeetingActionItemController::class, 'unlinkTask'])->name('tasks.unlink');
    });

    Route::prefix('{meeting}/participants')->name('participants.')->group(function () {
        Route::get('/', [MeetingParticipantController::class, 'index'])->name('index');
        Route::get('/create', [MeetingParticipantController::class, 'create'])->name('create');
        Route::post('/', [MeetingParticipantController::class, 'store'])->name('store');
        Route::get('/{participant}/edit', [MeetingParticipantController::class, 'edit'])->name('edit');
        Route::put('/{participant}', [MeetingParticipantController::class, 'update'])->name('update');
        Route::delete('/{participant}', [MeetingParticipantController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('{meeting}/attachments')->name('attachments.')->group(function () {
        Route::get('/', [MeetingAttachmentController::class, 'index'])->name('index');
        Route::get('/create', [MeetingAttachmentController::class, 'create'])->name('create');
        Route::post('/', [MeetingAttachmentController::class, 'store'])->name('store');
        Route::delete('/{attachment}', [MeetingAttachmentController::class, 'destroy'])->name('destroy');
    });
});
