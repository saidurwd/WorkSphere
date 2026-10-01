<?php

namespace App\Http\Controllers;

use App\Dashboard\DashboardWidget;
use App\Dashboard\WidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The dashboard — a composition of permission-gated widgets.
 *
 * This controller used to be 231 lines of inline aggregation with a
 * `hasRole('super-admin')` check deciding what each user saw. It now holds no
 * queries at all: every figure comes from a {@see DashboardWidget},
 * and visibility is decided by permissions in the registry.
 *
 * The one thing it still does is supply the view with the variable names it
 * expects. That mapping is deliberate rather than incidental — the Blade template
 * is 247 lines and renaming its variables is a UI change, not a reporting one —
 * but it is the only aggregation-adjacent code left here, and there is no query
 * in this file for it to be mistaken for.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, WidgetRegistry $registry): View
    {
        $user = $request->user();

        $widgets = $registry->groupedFor($user);
        $data = $widgets->flatten(1)->mapWithKeys(
            static fn (array $entry): array => [$entry['widget']->key() => $entry['data']],
        );

        return view('dashboard.index', [
            ...$this->legacyVariables($data),
            'widgets' => $widgets,
            'visibleWidgetKeys' => $data->keys()->all(),
            'breadcrumbs' => [
                ['label' => 'Home'],
                ['label' => 'Dashboard'],
            ],
        ]);
    }

    /**
     * Map widget output onto the names the existing template reads.
     *
     * Kept in one method so the removal is a single, obvious edit rather than a
     * hunt through a controller.
     *
     * @param  Collection<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function legacyVariables(Collection $data): array
    {
        $stats = $data->get('personal_stats', []);

        return [
            'taskTotal' => $stats['tasks']['total'] ?? 0,
            'taskCompleted' => $stats['tasks']['completed'] ?? 0,
            'taskPending' => $stats['tasks']['open'] ?? 0,
            'taskOverdue' => $stats['tasks']['overdue'] ?? 0,

            'meetingThisMonth' => $stats['meetings']['total'] ?? 0,
            'meetingUpcoming' => $stats['meetings']['upcoming'] ?? 0,
            'meetingCompleted' => $stats['meetings']['completed'] ?? 0,

            'obligationActive' => $data->get('obligation_expiry.active', 0),
            'obligationDue7' => $data->get('obligation_expiry.due_7', 0),
            'obligationDue30' => $data->get('obligation_expiry.due_30', 0),
            'obligationExpired' => $data->get('obligation_expiry.expired', 0),

            // Panels the new widgets supersede. The template keeps rendering the
            // shapes it already knows; these are derived, not re-queried.
            'upcomingTasks' => $data->get('upcoming_deadlines', collect()),
            'overdueTasks' => $data->get('overdue_items', collect()),
            'todayTasks' => $data->get('upcoming_deadlines', collect()),
            'myPendingActions' => collect(),
            'upcomingMeetings' => $data->get('upcoming_meetings', collect()),
            'upcomingObligations' => $data->get('critical_deadlines', collect()),
            'criticalObligations' => $data->get('critical_deadlines', collect()),
            'expiredObligations' => $data->get('overdue_items', collect()),

            'pendingActions' => 0,
            'overdueActions' => 0,
            'actionsDueThisWeek' => 0,
            'obligationCritical' => $stats['tasks']['total'] ?? 0,
            'obligationHighRisk' => 0,
            'obligationRenewal' => 0,
            'obligationPendingApproval' => 0,

            'statusDonut' => $data->get('task_distribution', collect()),
            'statusTotal' => (int) $data->get('task_distribution', collect())->sum('value'),
            'priorityBars' => collect(),
            'priorityDonut' => $data->get('obligation_expiry.priorityDonut', collect()),
            'priorityTotal' => array_sum(array_map(
                static fn (array $row): int => (int) ($row['value'] ?? 0),
                $data->get('obligation_expiry.priorityDonut', collect())->all(),
            )),
            'weeklyBars' => $data->get('personal_stats.weeklyBars', collect()),
            'typeBars' => $data->get('obligation_expiry.typeBars', collect()),
        ];
    }
}
