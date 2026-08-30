<?php

namespace App\Services;

use App\Models\NotificationRule;
use App\Models\Obligation;
use App\Models\ObligationActivityLog;

class ObligationService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected RiskService $riskService,
        protected EscalationService $escalationService,
    ) {}

    public function processObligation(Obligation $obligation): void
    {
        $oldRisk = $obligation->risk_level;
        $newRisk = $this->riskService->calculate($obligation);

        if ($oldRisk !== $newRisk) {
            $obligation->update(['risk_level' => $newRisk]);

            ObligationActivityLog::create([
                'obligation_id' => $obligation->id,
                'user_id' => null,
                'action' => 'RISK_CHANGED',
                'old_value' => json_encode(['risk_level' => $oldRisk]),
                'new_value' => json_encode(['risk_level' => $newRisk]),
                'remarks' => 'Automated risk recalculation',
            ]);
        }

        $rules = NotificationRule::query()
            ->where(function ($query) use ($obligation) {
                $query->where('obligation_type_id', $obligation->obligation_type_id)
                    ->orWhereNull('obligation_type_id');
            })
            ->where('active', true)
            ->get();

        $daysRemaining = (int) now()->startOfDay()->diffInDays($obligation->expiry_date, false);

        foreach ($rules as $rule) {
            if ($daysRemaining < 0) {
                if ($rule->days_before_expiry !== 0) {
                    continue;
                }
            } elseif ($daysRemaining !== $rule->days_before_expiry) {
                continue;
            }

            $recipient = $this->notificationService->getRecipient($obligation, $rule->recipient_type);

            if (! $recipient) {
                continue;
            }

            if ($this->notificationService->isAlreadySent($obligation, $rule, $recipient)) {
                continue;
            }

            $this->notificationService->sendObligationNotification($obligation, $rule, $recipient);
        }

        if ($daysRemaining < 0 && ! in_array($obligation->status, ['renewed', 'cancelled', 'not_required', 'archived'], true)) {
            $this->escalationService->escalate($obligation);
        }
    }
}
