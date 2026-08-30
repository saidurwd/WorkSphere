<?php

namespace App\Http\Controllers;

use App\Models\Obligation;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObligationReportController extends Controller
{
    public function index(): View
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

        $expiryReport = (clone $query)
            ->selectRaw('obligation_types.type_name, COUNT(obligations.id) as total')
            ->join('obligation_types', 'obligations.obligation_type_id', '=', 'obligation_types.id')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays(90)])
            ->groupBy('obligation_types.type_name')
            ->orderByDesc('total')
            ->get();

        $departmentStats = (clone $query)
            ->selectRaw('departments.department_name, COUNT(obligations.id) as total, SUM(CASE WHEN obligations.expiry_date < ? THEN 1 ELSE 0 END) as expired, SUM(CASE WHEN obligations.risk_level = ? THEN 1 ELSE 0 END) as critical', [$today, 'critical'])
            ->join('departments', 'obligations.department_id', '=', 'departments.id')
            ->groupBy('departments.department_name')
            ->orderByDesc('total')
            ->get();

        $vendorStats = (clone $query)
            ->selectRaw('vendors.vendor_name, COUNT(obligations.id) as total, SUM(obligations.estimated_cost) as total_cost')
            ->join('vendors', 'obligations.vendor_id', '=', 'vendors.id')
            ->whereNotNull('obligations.vendor_id')
            ->groupBy('vendors.vendor_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $renewalStats = (clone $query)
            ->selectRaw('
                SUM(CASE WHEN status IN ("active", "renewed") AND expiry_date >= ? THEN 1 ELSE 0 END) as on_time,
                SUM(CASE WHEN status IN ("active", "renewed") AND expiry_date < ? THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired_count,
                AVG(DATEDIFF(obligations.updated_at, obligations.start_date)) as avg_lead_time
            ', [$today, $today])
            ->first();

        $riskReport = (clone $query)
            ->whereIn('risk_level', ['critical', 'high'])
            ->orWhere(function ($q2) use ($today) {
                $q2->where('expiry_date', '<', $today)->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived']);
            })
            ->orderByDesc('risk_level')
            ->orderBy('expiry_date', 'asc')
            ->take(20)
            ->get();

        return view('obligations.reports', [
            'expiryReport' => $expiryReport,
            'departmentStats' => $departmentStats,
            'vendorStats' => $vendorStats,
            'renewalStats' => $renewalStats,
            'riskReport' => $riskReport,
        ]);
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
