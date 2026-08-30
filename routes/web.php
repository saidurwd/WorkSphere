<?php

use App\Http\Controllers\Api\DlrController as ApiDlrController;
use App\Http\Controllers\AssetManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DlrController;
use App\Http\Controllers\EstateStaffController;
use App\Http\Controllers\GatePassController;
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

Route::middleware(['web', 'auth'])->prefix('obligations')->name('obligations.')->group(function () {
    Route::get('/dashboard', [ObligationDashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/reports', [ObligationReportController::class, 'index'])->name('reports');
    Route::get('/calendar', [ObligationCalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [ObligationCalendarController::class, 'events'])->name('calendar.events');

    Route::get('/my-tasks', [ObligationMyTaskController::class, 'index'])->name('my-tasks');
    Route::get('/renewals', [ObligationRenewalListController::class, 'index'])->name('renewals');
    Route::get('/vendors', [ObligationVendorController::class, 'index'])->name('vendors');
    Route::get('/documents', [ObligationDocumentListController::class, 'index'])->name('documents');
    Route::get('/notifications', [ObligationNotificationController::class, 'index'])->name('notifications');

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

Route::middleware(['web', 'auth'])->prefix('estate-staff')->name('estate-staff.')->group(function () {
    Route::get('/{estateStaff}/print', [EstateStaffController::class, 'print'])->name('print');
    Route::get('/divisions', [EstateStaffController::class, 'divisions'])->name('divisions');
});

Route::middleware(['web', 'auth'])->prefix('gate-passes')->name('gate-passes.')->group(function () {
    Route::get('/', [GatePassController::class, 'index'])->name('index');
    Route::get('/create', [GatePassController::class, 'create'])->name('create');
    Route::post('/', [GatePassController::class, 'store'])->name('store');
    Route::get('/dashboard', [GatePassController::class, 'dashboard'])->name('dashboard');
    Route::get('/{gatePass}/print', [GatePassController::class, 'print'])->name('print');
    Route::get('/{gatePass}/edit', [GatePassController::class, 'edit'])->name('edit');
    Route::put('/{gatePass}', [GatePassController::class, 'update'])->name('update');
    Route::delete('/{gatePass}', [GatePassController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, '__invoke'])->name('index');
    Route::get('asset-management', [AssetManagementController::class, '__invoke'])
        ->middleware(['tyro-dashboard.admin'])
        ->name('asset-management.index');
});

Route::middleware(['web', 'auth'])->get('tyro-dashboard', function () {
    return redirect()->route('dashboard.index');
})->name('tyro-dashboard.index');

Route::middleware(['web', 'auth', 'tyro-dashboard.admin'])
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

        Route::get('dlr-sync', [DlrController::class, 'index'])
            ->name('dlr-sync.index');
        Route::post('dlr-sync/fetch', [DlrController::class, 'fetch'])
            ->name('dlr-sync.fetch');
        Route::post('dlr-sync', [DlrController::class, 'sync'])
            ->name('dlr-sync.sync');
        Route::get('dlr-sync/manage', [DlrController::class, 'manage'])
            ->name('dlr-sync.manage');

        Route::get('api/dlr/fetch', [ApiDlrController::class, 'fetch'])
            ->name('api.dlr.fetch');
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
