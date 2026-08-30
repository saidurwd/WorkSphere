<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingDecision;
use App\Models\Obligation;
use App\Models\ObligationType;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $tasksQuery = Task::query();
        if (! $this->canManageAll($user)) {
            $tasksQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id);
            });
        }

        $today = now()->startOfDay();
        $weekEnd = $today->copy()->endOfWeek();
        $tomorrow = $today->copy()->addDay();

        $taskTotal = (clone $tasksQuery)->count();
        $taskCompleted = (clone $tasksQuery)->where('status', 'completed')->count();
        $taskPending = $taskTotal - $taskCompleted;
        $taskOverdue = (clone $tasksQuery)->where('status', '!=', 'completed')->where('due_date', '<', $today)->count();

        $todayTasks = (clone $tasksQuery)->whereDate('due_date', $today)->orderBy('due_date')->limit(5)->get();
        $overdueTasks = (clone $tasksQuery)->where('status', '!=', 'completed')->where('due_date', '<', $today)->orderBy('due_date')->limit(5)->get();
        $upcomingTasks = (clone $tasksQuery)->where('status', '!=', 'completed')->whereBetween('due_date', [$tomorrow, $weekEnd])->orderBy('due_date')->limit(5)->get();

        $statusCounts = [
            ['status' => 'pending', 'label' => 'Pending', 'count' => (clone $tasksQuery)->where('status', 'pending')->count()],
            ['status' => 'in_progress', 'label' => 'In Progress', 'count' => (clone $tasksQuery)->where('status', 'in_progress')->count()],
            ['status' => 'completed', 'label' => 'Completed', 'count' => $taskCompleted],
        ];
        $statusTotal = array_sum(array_column($statusCounts, 'count'));
        $statusDonut = collect($statusCounts)->map(fn ($s) => [
            'label' => $s['label'],
            'count' => $s['count'],
            'pct' => $statusTotal > 0 ? (int) round($s['count'] / $statusTotal * 100) : 0,
            'color' => match ($s['status']) {
                'pending' => 'var(--warning)',
                'in_progress' => 'var(--info)',
                'completed' => 'var(--success)',
            },
        ])->all();

        $weeklyBars = collect(range(0, 6))->map(function ($dayOffset) use ($tasksQuery, $today) {
            $date = $today->copy()->startOfWeek()->addDays($dayOffset);
            $count = (clone $tasksQuery)->whereDate('created_at', $date)->count();
            $maxCount = max((clone $tasksQuery)->count(), 1);

            return [
                'label' => $date->format('D'),
                'value' => $count,
                'pct' => (int) round($count / $maxCount * 100),
            ];
        })->all();

        $priorityCounts = [
            ['priority' => 'high', 'label' => 'High', 'count' => (clone $tasksQuery)->where('priority', 'high')->count()],
            ['priority' => 'medium', 'label' => 'Medium', 'count' => (clone $tasksQuery)->where('priority', 'medium')->count()],
            ['priority' => 'low', 'label' => 'Low', 'count' => (clone $tasksQuery)->where('priority', 'low')->count()],
        ];
        $priorityTotal = array_sum(array_column($priorityCounts, 'count'));
        $priorityBars = collect($priorityCounts)->map(fn ($p) => [
            'label' => $p['label'],
            'value' => $p['count'],
            'pct' => $priorityTotal > 0 ? (int) round($p['count'] / $priorityTotal * 100) : 0,
            'color' => match ($p['priority']) {
                'high' => 'var(--destructive)',
                'medium' => 'var(--info)',
                'low' => 'var(--success)',
            },
        ])->all();

        $meetingQuery = Meeting::query();
        if (! $this->canManageAll($user)) {
            $meetingQuery->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhereHas('participants', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            });
        }

        $thisMonth = now()->startOfMonth();
        $meetingThisMonth = (clone $meetingQuery)->whereMonth('meeting_date', $thisMonth->month)->whereYear('meeting_date', $thisMonth->year)->count();
        $meetingUpcoming = (clone $meetingQuery)->where('status', 'scheduled')->whereDate('meeting_date', '>=', $today)->count();
        $meetingCompleted = (clone $meetingQuery)->where('status', 'completed')->count();
        $pendingActions = MeetingActionItem::where('status', 'open')->count();
        $overdueActions = MeetingActionItem::overdue()->count();
        $actionsDueThisWeek = MeetingActionItem::where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->whereBetween('due_date', [$today, $today->copy()->endOfWeek()])->count();
        $myPendingActions = MeetingActionItem::where('assigned_to', $user->id)->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->with('meeting')->orderBy('due_date')->limit(5)->get();
        $upcomingMeetings = (clone $meetingQuery)->where('status', 'scheduled')->whereDate('meeting_date', '>=', $today)->orderBy('meeting_date')->limit(5)->get();

        $obligationQuery = Obligation::query();
        if (! $this->canManageAll($user)) {
            $obligationQuery->where(function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id)->where('active', true);
                    });
            });
        }

        $obligationActive = (clone $obligationQuery)->where('status', 'active')->count();
        $obligationDue7 = (clone $obligationQuery)->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)])->count();
        $obligationDue30 = (clone $obligationQuery)->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])->count();
        $obligationExpired = (clone $obligationQuery)->where('expiry_date', '<', $today)->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived'])->count();
        $obligationCritical = (clone $obligationQuery)->where('risk_level', 'critical')->count();
        $obligationHighRisk = (clone $obligationQuery)->where('risk_level', 'high')->count();
        $obligationRenewal = (clone $obligationQuery)->where('status', 'renewal_in_progress')->count();
        $obligationPendingApproval = (clone $obligationQuery)->where('status', 'pending_approval')->count();

        $upcomingObligations = (clone $obligationQuery)->orderBy('expiry_date', 'asc')->take(5)->get();
        $criticalObligations = (clone $obligationQuery)->where('risk_level', 'critical')->orderBy('expiry_date', 'asc')->take(5)->get();
        $expiredObligations = (clone $obligationQuery)->where('expiry_date', '<', $today)->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived'])->orderBy('expiry_date', 'asc')->take(5)->get();

        $typeStats = ObligationType::query()
            ->select('obligation_types.id', 'obligation_types.type_name')
            ->selectRaw('COUNT(obligations.id) as total')
            ->leftJoin('obligations', 'obligations.obligation_type_id', '=', 'obligation_types.id')
            ->groupBy('obligation_types.id', 'obligation_types.type_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $typeMax = $typeStats->max('total') ?: 1;
        $typeBars = $typeStats->map(fn ($row) => [
            'label' => $row->type_name,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $typeMax * 100),
            'color' => 'var(--info)',
        ])->all();

        $priorityStats = Obligation::query()->select('priority')->selectRaw('COUNT(*) as total')->groupBy('priority')->get();
        $priorityTotalObligation = $priorityStats->sum('total');
        $priorityDonut = $priorityStats->map(fn ($row) => [
            'label' => ucfirst($row->priority),
            'count' => (int) $row->total,
            'pct' => $priorityTotalObligation > 0 ? (int) round((int) $row->total / $priorityTotalObligation * 100) : 0,
            'color' => match ($row->priority) {
                'critical' => 'var(--destructive)',
                'high' => 'var(--warning)',
                'medium' => 'var(--info)',
                'low' => 'var(--success)',
            },
        ])->all();

        $mapTask = static function (Task $task): array {
            return [
                'title' => $task->title,
                'subtitle' => 'Due '.$task->due_date->format('M d, Y'),
                'url' => route('tasks.edit', $task),
                'badge' => [
                    'text' => ucfirst($task->priority),
                    'class' => $task->priority === 'high'
                        ? 'badge-danger'
                        : ($task->priority === 'medium' ? 'badge-primary' : 'badge-secondary'),
                ],
            ];
        };

        $mapObligation = static function (Obligation $o): array {
            $remaining = $today->diffInDays($o->expiry_date, false);
            $remainingText = $remaining < 0 ? 'Expired '.abs($remaining).' days ago' : ($remaining === 0 ? 'Expires today' : $remaining.' days remaining');

            return [
                'title' => $o->title,
                'subtitle' => $remainingText,
                'url' => route('obligations.show', $o),
                'badge' => [
                    'text' => ucfirst($o->risk_level),
                    'class' => match ($o->risk_level) {
                        'critical' => 'badge-danger',
                        'high' => 'badge-warning',
                        'medium' => 'badge-primary',
                        'low' => 'badge-secondary',
                    },
                ],
            ];
        };

        return view('dashboard.index', [
            'taskTotal' => $taskTotal,
            'taskCompleted' => $taskCompleted,
            'taskPending' => $taskPending,
            'taskOverdue' => $taskOverdue,
            'meetingThisMonth' => $meetingThisMonth,
            'meetingUpcoming' => $meetingUpcoming,
            'meetingCompleted' => $meetingCompleted,
            'pendingActions' => $pendingActions,
            'overdueActions' => $overdueActions,
            'actionsDueThisWeek' => $actionsDueThisWeek,
            'obligationActive' => $obligationActive,
            'obligationDue7' => $obligationDue7,
            'obligationDue30' => $obligationDue30,
            'obligationExpired' => $obligationExpired,
            'obligationCritical' => $obligationCritical,
            'obligationHighRisk' => $obligationHighRisk,
            'obligationRenewal' => $obligationRenewal,
            'obligationPendingApproval' => $obligationPendingApproval,
            'todayTasks' => $todayTasks->map($mapTask)->all(),
            'overdueTasks' => $overdueTasks->map($mapTask)->all(),
            'upcomingTasks' => $upcomingTasks->map($mapTask)->all(),
            'statusDonut' => $statusDonut,
            'statusTotal' => $statusTotal,
            'weeklyBars' => $weeklyBars,
            'priorityBars' => $priorityBars,
            'upcomingMeetings' => $upcomingMeetings,
            'myPendingActions' => $myPendingActions,
            'upcomingObligations' => $upcomingObligations->map($mapObligation)->all(),
            'criticalObligations' => $criticalObligations->map($mapObligation)->all(),
            'expiredObligations' => $expiredObligations->map($mapObligation)->all(),
            'typeBars' => $typeBars,
            'priorityDonut' => $priorityDonut,
            'priorityTotal' => $priorityTotalObligation,
        ]);
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
