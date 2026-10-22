<?php

namespace Modules\Meetings\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingDecision;

/**
 * The numbers behind the meeting overview and accountability reports.
 *
 * Every figure is limited to what the viewer may see — their meetings, their
 * decisions, their action items — which the Blade dashboard and reports were not:
 * action and decision counts, and the person- and department-wise tables, covered
 * the whole company for everyone.
 */
class MeetingInsights
{
    /**
     * @return array{stats: array<string, int>, upcoming: Collection<int, Meeting>, mine: Collection<int, MeetingActionItem>}
     */
    public function overview(User $user): array
    {
        $meetings = fn () => Meeting::query()->visibleTo($user);
        $actions = fn () => MeetingActionItem::query()->visibleTo($user);
        $open = fn () => $actions()->whereNotIn('status', ['completed', 'cancelled']);

        $month = now()->startOfMonth();
        $today = now()->startOfDay();

        return [
            'stats' => [
                'Meetings this month' => $meetings()->whereMonth('meeting_date', $month->month)->whereYear('meeting_date', $month->year)->count(),
                'Upcoming' => $meetings()->where('status', 'scheduled')->whereDate('meeting_date', '>=', $today)->count(),
                'Completed' => $meetings()->where('status', 'completed')->count(),
                'Minutes in draft' => $meetings()->where('minutes_status', 'draft')->count(),
                'Awaiting approval' => $meetings()->where('minutes_status', 'submitted')->count(),
                'Open actions' => $actions()->where('status', 'open')->count(),
                'Overdue actions' => $actions()->overdue()->count(),
                'Actions due this week' => $open()->whereBetween('due_date', [$today, $today->copy()->endOfWeek()])->count(),
                'Decisions this month' => MeetingDecision::query()->visibleTo($user)->whereMonth('decision_date', $month->month)->whereYear('decision_date', $month->year)->count(),
                'My pending actions' => MeetingActionItem::query()->where('assigned_to', $user->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
            'upcoming' => $meetings()->where('status', 'scheduled')->whereDate('meeting_date', '>=', $today)->orderBy('meeting_date')->limit(5)->get(),
            'mine' => MeetingActionItem::query()
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with('meeting:id,title')
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Open, in-progress, completed and overdue action items per assignee.
     *
     * @return Collection<int, object>
     */
    public function personWise(User $user): Collection
    {
        $today = now()->toDateString();

        return MeetingActionItem::query()
            ->visibleTo($user)
            ->whereNotNull('assigned_to')
            ->select('assigned_to')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('completed', 'cancelled') AND due_date < ? THEN 1 ELSE 0 END) as overdue", [$today])
            ->groupBy('assigned_to')
            ->with('assignedTo:id,name')
            ->get();
    }

    /**
     * Action items per department with how many are done, pending and late.
     *
     * @return Collection<int, object>
     */
    public function departmentWise(User $user): Collection
    {
        $today = now()->toDateString();

        return MeetingActionItem::query()
            ->visibleTo($user)
            ->whereNotNull('assigned_department_id')
            ->select('assigned_department_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('completed', 'cancelled') AND due_date < ? THEN 1 ELSE 0 END) as overdue", [$today])
            ->groupBy('assigned_department_id')
            ->with('assignedDepartment:id,department_name')
            ->get();
    }
}
