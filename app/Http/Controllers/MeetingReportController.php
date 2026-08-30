<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\User;
use App\Services\MeetingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingReportController extends Controller
{
    public function __construct(private MeetingReportService $reportService) {}

    public function meetings(Request $request): View
    {
        $filters = $request->only(['date_from', 'date_to', 'department_id', 'meeting_type_id', 'organizer_id', 'status', 'minutes_status', 'search']);
        $meetings = $this->reportService->meetingSummary($filters);

        $types = MeetingType::orderBy('sort_order')->get();
        $departments = Department::orderBy('department_name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.reports.meetings', compact('meetings', 'types', 'departments', 'users', 'filters'));
    }

    public function actions(Request $request): View
    {
        $filters = $request->only(['meeting_id', 'assigned_to', 'department_id', 'status', 'priority', 'overdue', 'search']);
        $actions = $this->reportService->actionItemReport($filters);

        $meetings = Meeting::orderByDesc('meeting_date')->get(['id', 'title', 'meeting_no']);
        $users = User::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('department_name')->get();

        return view('meetings.reports.actions', compact('actions', 'meetings', 'users', 'departments', 'filters'));
    }

    public function overdueActions(): View
    {
        $overdue = $this->reportService->overdueActions();

        return view('meetings.reports.overdue', compact('overdue'));
    }

    public function personWise(): View
    {
        $report = $this->reportService->personWiseAccountability();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.reports.person_wise', compact('report', 'users'));
    }

    public function departmentWise(): View
    {
        $report = $this->reportService->departmentPerformance();
        $departments = Department::orderBy('department_name')->get();

        return view('meetings.reports.department_wise', compact('report', 'departments'));
    }

    public function decisions(Request $request): View
    {
        $filters = $request->only(['meeting_id', 'decision_type', 'decision_status', 'date_from', 'date_to', 'search']);
        $decisions = $this->reportService->decisionRegister($filters);

        $meetings = Meeting::orderByDesc('meeting_date')->get(['id', 'title', 'meeting_no']);

        return view('meetings.reports.decisions', compact('decisions', 'meetings', 'filters'));
    }

    public function index(): View
    {
        return view('meetings.reports.index');
    }
}
