<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\GatePass;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $tasksQuery = Task::query();

        if (! $this->canManageAllTasks($user)) {
            $tasksQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id);
            });
        }

        $total = (clone $tasksQuery)->count();
        $completed = (clone $tasksQuery)->where('status', 'completed')->count();
        $pending = $total - $completed;

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $weekEnd = $today->copy()->endOfWeek();

        $todayTasks = (clone $tasksQuery)
            ->whereDate('due_date', $today)
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        $overdueTasks = (clone $tasksQuery)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', $today)
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        $completedTasks = (clone $tasksQuery)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->take(5)
            ->get();
        $upcomingTasks = (clone $tasksQuery)
            ->where('status', '!=', 'completed')
            ->whereBetween('due_date', [$tomorrow, $weekEnd])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        $highPriorityTasks = (clone $tasksQuery)
            ->where('priority', 'high')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

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

        $statusCounts = [
            ['status' => 'pending', 'label' => 'Pending', 'count' => (clone $tasksQuery)->where('status', 'pending')->count()],
            ['status' => 'in_progress', 'label' => 'In Progress', 'count' => (clone $tasksQuery)->where('status', 'in_progress')->count()],
            ['status' => 'completed', 'label' => 'Completed', 'count' => $completed],
        ];
        $statusTotal = $statusCounts[0]['count'] + $statusCounts[1]['count'] + $statusCounts[2]['count'];
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

        $weekStart = $today->copy()->startOfWeek();
        $weeklyBars = collect(range(0, 6))->map(function ($dayOffset) use ($tasksQuery, $weekStart) {
            $date = $weekStart->copy()->addDays($dayOffset);
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
        $priorityTotal = $priorityCounts[0]['count'] + $priorityCounts[1]['count'] + $priorityCounts[2]['count'];
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

        $assetStatusRaw = Asset::query()
            ->selectRaw('current_status, count(*) as total')
            ->groupBy('current_status')
            ->pluck('total', 'current_status');
        $assetStatusCounts = [
            ['status' => 'In Stock', 'label' => 'In Stock', 'count' => (int) ($assetStatusRaw['In Stock'] ?? 0)],
            ['status' => 'Assigned', 'label' => 'Assigned', 'count' => (int) ($assetStatusRaw['Assigned'] ?? 0)],
            ['status' => 'Under Repair', 'label' => 'Under Repair', 'count' => (int) ($assetStatusRaw['Under Repair'] ?? 0)],
            ['status' => 'Spare', 'label' => 'Spare', 'count' => (int) ($assetStatusRaw['Spare'] ?? 0)],
            ['status' => 'Disposed', 'label' => 'Disposed', 'count' => (int) ($assetStatusRaw['Disposed'] ?? 0)],
        ];
        $assetStatusTotal = array_sum(array_column($assetStatusCounts, 'count'));
        $assetStatusDonut = collect($assetStatusCounts)->map(fn ($s) => [
            'label' => $s['label'],
            'count' => $s['count'],
            'pct' => $assetStatusTotal > 0 ? (int) round($s['count'] / $assetStatusTotal * 100) : 0,
            'color' => match ($s['status']) {
                'In Stock' => 'var(--success)',
                'Assigned' => 'var(--info)',
                'Under Repair' => 'var(--warning)',
                'Spare' => 'var(--primary)',
                'Disposed' => 'var(--destructive)',
            },
        ])->all();

        $assetCategoryBars = Asset::query()
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->selectRaw('asset_categories.category_name, count(*) as total')
            ->groupBy('asset_categories.category_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $assetCategoryMax = $assetCategoryBars->max('total') ?: 1;
        $assetCategoryBars = $assetCategoryBars->map(fn ($row) => [
            'label' => $row->category_name,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $assetCategoryMax * 100),
            'color' => 'var(--info)',
        ])->all();

        $gatePassChecked = GatePass::whereNotNull('checked_by')->where('checked_by', '<>', '')->count();
        $gatePassPending = GatePass::count() - $gatePassChecked;
        $gatePassStatusCounts = [
            ['status' => 'checked', 'label' => 'Checked', 'count' => $gatePassChecked],
            ['status' => 'pending', 'label' => 'Pending Check', 'count' => $gatePassPending],
        ];
        $gatePassStatusTotal = $gatePassChecked + $gatePassPending;
        $gatePassStatusDonut = collect($gatePassStatusCounts)->map(fn ($s) => [
            'label' => $s['label'],
            'count' => $s['count'],
            'pct' => $gatePassStatusTotal > 0 ? (int) round($s['count'] / $gatePassStatusTotal * 100) : 0,
            'color' => match ($s['status']) {
                'checked' => 'var(--success)',
                'pending' => 'var(--warning)',
            },
        ])->all();

        $gatePassWeekStart = $today->copy()->startOfWeek();
        $gatePassWeeklyBars = collect(range(0, 6))->map(function ($dayOffset) use ($gatePassWeekStart) {
            $date = $gatePassWeekStart->copy()->addDays($dayOffset);
            $count = GatePass::whereDate('issue_date', $date)->count();
            $maxCount = max(GatePass::count(), 1);

            return [
                'label' => $date->format('D'),
                'value' => $count,
                'pct' => (int) round($count / $maxCount * 100),
            ];
        })->all();

        $maintenanceStatusRaw = MaintenanceRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $maintenanceStatusCounts = [
            ['status' => 'open', 'label' => 'Open', 'count' => (int) ($maintenanceStatusRaw['open'] ?? 0)],
            ['status' => 'in_progress', 'label' => 'In Progress', 'count' => (int) ($maintenanceStatusRaw['in_progress'] ?? 0)],
            ['status' => 'resolved', 'label' => 'Resolved', 'count' => (int) ($maintenanceStatusRaw['resolved'] ?? 0)],
            ['status' => 'closed', 'label' => 'Closed', 'count' => (int) ($maintenanceStatusRaw['closed'] ?? 0)],
        ];
        $maintenanceStatusTotal = array_sum(array_column($maintenanceStatusCounts, 'count'));
        $maintenanceStatusDonut = collect($maintenanceStatusCounts)->map(fn ($s) => [
            'label' => $s['label'],
            'count' => $s['count'],
            'pct' => $maintenanceStatusTotal > 0 ? (int) round($s['count'] / $maintenanceStatusTotal * 100) : 0,
            'color' => match ($s['status']) {
                'open' => 'var(--destructive)',
                'in_progress' => 'var(--info)',
                'resolved' => 'var(--success)',
                'closed' => 'var(--muted-foreground)',
            },
        ])->all();

        $maintenancePriorityCounts = MaintenanceRequest::query()
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->orderByDesc('total')
            ->get();
        $maintenancePriorityMax = $maintenancePriorityCounts->max('total') ?: 1;
        $maintenancePriorityBars = $maintenancePriorityCounts->map(fn ($row) => [
            'label' => $row->priority,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $maintenancePriorityMax * 100),
            'color' => match ($row->priority) {
                'Critical' => 'var(--destructive)',
                'High' => 'var(--warning)',
                'Medium' => 'var(--info)',
                'Low' => 'var(--success)',
            },
        ])->all();

        $maintenanceWeekStart = $today->copy()->startOfWeek();
        $maintenanceWeeklyBars = collect(range(0, 6))->map(function ($dayOffset) use ($maintenanceWeekStart) {
            $date = $maintenanceWeekStart->copy()->addDays($dayOffset);
            $count = MaintenanceHistory::whereDate('repair_date', $date)->count();
            $maxCount = max(MaintenanceHistory::count(), 1);

            return [
                'label' => $date->format('D'),
                'value' => $count,
                'pct' => (int) round($count / $maxCount * 100),
            ];
        })->all();

        $vendorBars = MaintenanceHistory::query()
            ->join('vendors', 'maintenance_history.vendor_id', '=', 'vendors.id')
            ->selectRaw('vendors.vendor_name, count(*) as total')
            ->groupBy('vendors.vendor_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $vendorMax = $vendorBars->max('total') ?: 1;
        $vendorBars = $vendorBars->map(fn ($row) => [
            'label' => $row->vendor_name,
            'value' => (int) $row->total,
            'pct' => (int) round((int) $row->total / $vendorMax * 100),
            'color' => 'var(--warning)',
        ])->all();

        return view('dashboard.index', [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'todayTasks' => $todayTasks->map($mapTask)->all(),
            'overdueTasks' => $overdueTasks->map($mapTask)->all(),
            'completedTasks' => $completedTasks->map($mapTask)->all(),
            'upcomingTasks' => $upcomingTasks->map($mapTask)->all(),
            'highPriorityTasks' => $highPriorityTasks->map($mapTask)->all(),
            'viewAllTodayRoute' => route('tasks.index', ['due_date' => 'today']),
            'viewAllOverdueRoute' => route('tasks.index'),
            'viewAllCompletedRoute' => route('tasks.index', ['status' => 'completed']),
            'viewAllUpcomingRoute' => route('tasks.index', ['due_date' => 'this_week']),
            'viewAllHighPriorityRoute' => route('tasks.index', ['priority' => 'high']),
            'statusDonut' => $statusDonut,
            'statusTotal' => $statusTotal,
            'weeklyBars' => $weeklyBars,
            'priorityBars' => $priorityBars,
            'assetStatusDonut' => $assetStatusDonut,
            'assetStatusTotal' => $assetStatusTotal,
            'assetCategoryBars' => $assetCategoryBars,
            'gatePassStatusDonut' => $gatePassStatusDonut,
            'gatePassStatusTotal' => $gatePassStatusTotal,
            'gatePassWeeklyBars' => $gatePassWeeklyBars,
            'maintenanceStatusDonut' => $maintenanceStatusDonut,
            'maintenanceStatusTotal' => $maintenanceStatusTotal,
            'maintenancePriorityBars' => $maintenancePriorityBars,
            'maintenanceWeeklyBars' => $maintenanceWeeklyBars,
            'vendorBars' => $vendorBars,
        ]);
    }

    private function canManageAllTasks(mixed $user): bool
    {
        return method_exists($user, 'hasRole')
            && $user->hasRole('super-admin');
    }
}
