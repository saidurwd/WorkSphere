<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingDecision;
use App\Services\MeetingReportService;
use App\Services\MeetingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeetingDashboardController extends Controller
{
    public function __construct(
        private MeetingService $meetingService,
        private MeetingReportService $reportService
    ) {}

    public function __invoke(): View
    {
        $user = Auth::user();
        $query = Meeting::query();

        if (! $this->canManageAll($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhereHas('participants', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            });
        }

        $thisMonth = now()->startOfMonth();
        $today = now()->startOfDay();

        $stats = [
            'this_month' => (clone $query)->whereMonth('meeting_date', $thisMonth->month)->whereYear('meeting_date', $thisMonth->year)->count(),
            'upcoming' => (clone $query)->where('status', 'scheduled')->whereDate('meeting_date', '>=', $today)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'draft_minutes' => (clone $query)->where('minutes_status', 'draft')->count(),
            'awaiting_approval' => (clone $query)->where('minutes_status', 'submitted')->count(),
            'pending_actions' => MeetingActionItem::where('status', 'open')->count(),
            'overdue_actions' => MeetingActionItem::overdue()->count(),
            'actions_due_this_week' => MeetingActionItem::where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->whereBetween('due_date', [$today, $today->copy()->endOfWeek()])
                ->count(),
            'decisions_this_month' => MeetingDecision::whereMonth('decision_date', $thisMonth->month)->whereYear('decision_date', $thisMonth->year)->count(),
            'my_pending_actions' => MeetingActionItem::where('assigned_to', $user->id)
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->count(),
        ];

        $upcomingMeetings = (clone $query)->where('status', 'scheduled')->whereDate('meeting_date', '>=', $today)->orderBy('meeting_date')->limit(5)->get();
        $myPendingActions = MeetingActionItem::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->with('meeting')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return view('meetings.dashboard', compact('stats', 'upcomingMeetings', 'myPendingActions'));
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
