<?php

namespace App\Services;

use App\Models\Obligation;

class RiskService
{
    public function calculate(Obligation $obligation): string
    {
        if (in_array($obligation->status, ['expired', 'cancelled', 'not_required', 'archived', 'renewed'], true)) {
            return match ($obligation->status) {
                'expired' => 'critical',
                default => 'low',
            };
        }

        $daysRemaining = (int) now()->startOfDay()->diffInDays($obligation->expiry_date, false);
        $hasNoAction = $this->hasNoAction($obligation);

        if ($daysRemaining < 0) {
            return 'critical';
        }

        if ($daysRemaining <= 7) {
            return $hasNoAction ? 'critical' : 'critical';
        }

        if ($daysRemaining <= 30) {
            return $hasNoAction ? 'critical' : 'high';
        }

        if ($daysRemaining <= 90) {
            return $hasNoAction ? 'high' : 'medium';
        }

        return match ($obligation->priority) {
            'critical' => 'high',
            'high' => 'medium',
            default => 'low',
        };
    }

    public function hasNoAction(Obligation $obligation): bool
    {
        if ($obligation->status === 'renewed' || $obligation->status === 'expired') {
            return false;
        }

        $daysRemaining = (int) now()->startOfDay()->diffInDays($obligation->expiry_date, false);

        if ($daysRemaining > 30) {
            return false;
        }

        $activeRenewal = $obligation->renewals()
            ->where('new_expiry_date', '>', now())
            ->exists();

        if ($activeRenewal) {
            return false;
        }

        $inProgressTask = $obligation->tasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($inProgressTask) {
            return false;
        }

        return true;
    }
}
