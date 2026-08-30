<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingDecision;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MeetingReportService
{
    public function meetingSummary(array $filters = []): LengthAwarePaginator
    {
        $query = Meeting::query()
            ->with(['type', 'organizer', 'department'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('meeting_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('meeting_date', '<=', $date))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->when($filters['meeting_type_id'] ?? null, fn ($q, $id) => $q->where('meeting_type_id', $id))
            ->when($filters['organizer_id'] ?? null, fn ($q, $id) => $q->where('organizer_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['minutes_status'] ?? null, fn ($q, $status) => $q->where('minutes_status', $status))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('meeting_no', 'like', "%{$search}%");
                });
            });

        return $query->orderByDesc('meeting_date')->paginate(15);
    }

    public function actionItemReport(array $filters = []): LengthAwarePaginator
    {
        $query = MeetingActionItem::query()
            ->with(['meeting', 'assignedTo', 'assignedDepartment', 'task'])
            ->when($filters['meeting_id'] ?? null, fn ($q, $id) => $q->where('meeting_id', $id))
            ->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('assigned_department_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['overdue'] ?? false, fn ($q) => $q->overdue())
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });

        return $query->orderByDesc('due_date')->paginate(15);
    }

    public function overdueActions(): Collection
    {
        return MeetingActionItem::query()
            ->with(['meeting', 'assignedTo', 'assignedDepartment', 'task'])
            ->overdue()
            ->orderBy('due_date')
            ->get();
    }

    public function personWiseAccountability(): Collection
    {
        return MeetingActionItem::query()
            ->select(
                'assigned_to',
                DB::raw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open"),
                DB::raw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status != 'completed' AND status != 'cancelled' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue")
            )
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->with('assignedTo')
            ->get();
    }

    public function departmentPerformance(): Collection
    {
        return MeetingActionItem::query()
            ->select(
                'assigned_department_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status != 'completed' AND status != 'cancelled' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status != 'completed' AND status != 'cancelled' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue")
            )
            ->whereNotNull('assigned_department_id')
            ->groupBy('assigned_department_id')
            ->with('assignedDepartment')
            ->get();
    }

    public function decisionRegister(array $filters = []): LengthAwarePaginator
    {
        $query = MeetingDecision::query()
            ->with(['meeting', 'approvedBy'])
            ->when($filters['meeting_id'] ?? null, fn ($q, $id) => $q->where('meeting_id', $id))
            ->when($filters['decision_type'] ?? null, fn ($q, $type) => $q->where('decision_type', $type))
            ->when($filters['decision_status'] ?? null, fn ($q, $status) => $q->where('decision_status', $status))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('decision_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('decision_date', '<=', $date))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('decision_title', 'like', "%{$search}%")
                        ->orWhere('decision_description', 'like', "%{$search}%");
                });
            });

        return $query->orderByDesc('decision_date')->paginate(15);
    }
}
