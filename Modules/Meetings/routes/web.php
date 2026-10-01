<?php

use Illuminate\Support\Facades\Route;
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
use Modules\Meetings\Http\Controllers\MeetingTemplateController;
use Modules\Meetings\Http\Controllers\MeetingTypeController;

/*
 * Routes owned by the Meetings module.
 *
 * Registered by Modules\Meetings\Providers\RouteServiceProvider with the
 * 'web' middleware group, which supplies session state and CSRF protection.
 */

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
    // Templates — GAP-028. Declared before `/{meeting}` so `meeting-templates`
    // cannot be captured as a meeting id.
    Route::prefix('meeting-templates')->name('templates.')->group(function () {
        Route::get('/', [MeetingTemplateController::class, 'index'])->name('index');
        Route::get('create', [MeetingTemplateController::class, 'create'])->name('create');
        Route::post('/', [MeetingTemplateController::class, 'store'])->name('store');
        Route::get('{template}', [MeetingTemplateController::class, 'show'])->name('show');
        Route::get('{template}/edit', [MeetingTemplateController::class, 'edit'])->name('edit');
        Route::put('{template}', [MeetingTemplateController::class, 'update'])->name('update');
        Route::delete('{template}', [MeetingTemplateController::class, 'destroy'])->name('destroy');
        Route::post('{template}/schedule', [MeetingTemplateController::class, 'schedule'])->name('schedule');
    });

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
