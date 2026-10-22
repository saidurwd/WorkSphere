<?php

use Illuminate\Support\Facades\Route;
use Modules\Meetings\Http\Controllers\MeetingPrintController;

/*
|--------------------------------------------------------------------------
| Meeting web routes
|--------------------------------------------------------------------------
|
| Only the printable sheet. Every other meeting screen (register, agenda,
| participants, decisions, action items, minutes, templates, types, tags,
| calendar, reports, notification log) is a Filament resource or page in the
| workspace panel, discovered from Modules/Meetings/app/Filament.
|
*/

Route::middleware(['web', 'auth'])->get('meetings/{meeting}/print', MeetingPrintController::class)->name('meetings.print');
