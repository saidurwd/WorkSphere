<?php

namespace App\Services;

use App\Mail\ObligationEscalation;
use App\Models\EscalationRule;
use App\Models\NotificationLog;
use App\Models\Obligation;
use App\Models\ObligationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EscalationService
{
    public function escalate(Obligation $obligation): void
    {
        $rules = EscalationRule::query()
            ->where(function ($query) use ($obligation) {
                $query->where('obligation_type_id', $obligation->obligation_type_id)
                    ->orWhereNull('obligation_type_id');
            })
            ->where('active', true)
            ->get();

        $daysRemaining = (int) now()->startOfDay()->diffInDays($obligation->expiry_date, false);

        foreach ($rules as $rule) {
            $matched = false;

            if ($rule->days_before_expiry !== null && $daysRemaining >= 0 && $daysRemaining <= $rule->days_before_expiry) {
                $matched = true;
            }

            if ($rule->days_after_expiry !== null && $daysRemaining < 0 && abs($daysRemaining) >= $rule->days_after_expiry) {
                $matched = true;
            }

            if (! $matched) {
                continue;
            }

            $recipient = $this->resolveRecipient($obligation, $rule->recipient_type);

            if (! $recipient) {
                continue;
            }

            $alreadySent = NotificationLog::where('obligation_id', $obligation->id)
                ->where('channel', $rule->channel)
                ->where('notification_type', $rule->escalation_level)
                ->where('user_id', $recipient->id)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $log = NotificationLog::create([
                'obligation_id' => $obligation->id,
                'user_id' => $recipient->id,
                'channel' => $rule->channel,
                'notification_type' => $rule->escalation_level,
                'scheduled_at' => now(),
                'status' => 'PENDING',
                'subject' => '[ESCALATION] '.$obligation->title.' requires attention',
                'message' => 'Obligation '.$obligation->obligation_no.' ('.$obligation->title.') has been escalated. Days remaining: '.abs($daysRemaining).'.',
            ]);

            try {
                if ($rule->channel === 'EMAIL') {
                    Mail::to($recipient->email)->send(new ObligationEscalation($obligation, $recipient));
                }

                $log->update([
                    'status' => 'SENT',
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $log->update([
                    'status' => 'FAILED',
                    'error_message' => $e->getMessage(),
                    'retry_count' => $log->retry_count + 1,
                ]);
            }

            ObligationActivityLog::create([
                'obligation_id' => $obligation->id,
                'user_id' => null,
                'action' => 'ESCALATED',
                'new_value' => json_encode(['escalation_level' => $rule->escalation_level, 'recipient_type' => $rule->recipient_type]),
                'remarks' => 'Escalation triggered',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    private function resolveRecipient(Obligation $obligation, string $recipientType): ?User
    {
        return match ($recipientType) {
            'OWNER' => $obligation->owner,
            'BACKUP_OWNER' => $obligation->backupUser,
            'MANAGER' => $obligation->owner,
            'DEPARTMENT_HEAD' => $obligation->department?->headOfDepartment?->user,
            'SPECIFIC_USER' => null,
            default => null,
        };
    }
}
