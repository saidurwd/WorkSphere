<?php

namespace App\Http\Controllers;

use App\Models\Obligation;
use App\Models\ObligationType;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObligationDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $query = Obligation::query();

        if (! $this->canManageAll($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id)->where('active', true);
                    });
            });
        }

        $today = now()->startOfDay();

        $active = (clone $query)->where('status', 'active')->count();
        $dueWithin7Days = (clone $query)->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)])->count();
        $dueWithin30Days = (clone $query)->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])->count();
        $expired = (clone $query)->where('expiry_date', '<', $today)->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived'])->count();
        $critical = (clone $query)->where('risk_level', 'critical')->count();
        $highRisk = (clone $query)->where('risk_level', 'high')->count();
        $renewalInProgress = (clone $query)->where('status', 'renewal_in_progress')->count();
        $pendingApproval = (clone $query)->where('status', 'pending_approval')->count();

        $overdueTasks = Task::query()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', $today)
            ->whereNotNull('obligation_id')
            ->count();

        $upcoming = (clone $query)->orderBy('expiry_date', 'asc')->take(10)->get();
        $criticalList = (clone $query)->where('risk_level', 'critical')->orderBy('expiry_date', 'asc')->take(10)->get();
        $expiredList = (clone $query)->where('expiry_date', '<', $today)->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived'])->orderBy('expiry_date', 'asc')->take(10)->get();

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

        $priorityStats = Obligation::query()
            ->select('priority')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('priority')
            ->get();

        $priorityTotal = $priorityStats->sum('total');
        $priorityDonut = $priorityStats->map(fn ($row) => [
            'label' => ucfirst($row->priority),
            'count' => (int) $row->total,
            'pct' => $priorityTotal > 0 ? (int) round((int) $row->total / $priorityTotal * 100) : 0,
            'color' => match ($row->priority) {
                'critical' => 'var(--destructive)',
                'high' => 'var(--warning)',
                'medium' => 'var(--info)',
                'low' => 'var(--success)',
            },
        ])->all();

        $mapObligation = static function (Obligation $o): array {
            $remaining = now()->startOfDay()->diffInDays($o->expiry_date, false);
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

        return view('obligations.dashboard', [
            'active' => $active,
            'dueWithin7Days' => $dueWithin7Days,
            'dueWithin30Days' => $dueWithin30Days,
            'expired' => $expired,
            'critical' => $critical,
            'highRisk' => $highRisk,
            'renewalInProgress' => $renewalInProgress,
            'pendingApproval' => $pendingApproval,
            'overdueTasks' => $overdueTasks,
            'upcoming' => $upcoming->map($mapObligation)->all(),
            'criticalList' => $criticalList->map($mapObligation)->all(),
            'expiredList' => $expiredList->map($mapObligation)->all(),
            'typeBars' => $typeBars,
            'priorityDonut' => $priorityDonut,
            'priorityTotal' => $priorityTotal,
            'viewAllRoute' => route('obligations.index'),
        ]);
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
