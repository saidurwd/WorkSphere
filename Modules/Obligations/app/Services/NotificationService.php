<?php

namespace Modules\Obligations\Services;

use App\Models\User;
use Modules\Obligations\Jobs\SendObligationReminderJob;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\NotificationRule;
use Modules\Obligations\Models\Obligation;

class NotificationService
{
    public function sendObligationNotification(Obligation $obligation, NotificationRule $rule, ?User $recipient): bool
    {
        $log = NotificationLog::create([
            'obligation_id' => $obligation->id,
            'user_id' => $recipient?->id,
            'notification_rule_id' => $rule->id,
            'channel' => $rule->channel,
            'notification_type' => $rule->notification_level,
            'scheduled_at' => now(),
            'status' => 'PENDING',
            'subject' => $this->buildSubject($obligation, $rule),
            'message' => $this->buildMessage($obligation, $rule),
            // Phase 6 added dedupe_key behind a unique index. Without one, a cron
            // firing twice sends twice.
            'dedupe_key' => sprintf(
                'obligation.reminder:%d:%d:%s',
                $obligation->id,
                $rule->id,
                now()->toDateString(),
            ),
        ]);

        // GAP-022: queued, never inline. The log row is written here and marked
        // SENT by the job, so the record reflects delivery rather than intent.
        SendObligationReminderJob::dispatch($obligation, $rule, $recipient, (int) $log->id);

        return true;
    }

    public function buildSubject(Obligation $obligation, NotificationRule $rule): string
    {
        $daysRemaining = now()->startOfDay()->diffInDays($obligation->expiry_date, false);
        $subject = $rule->subject_template ?? '[Reminder] '.$obligation->title.' expires soon';

        return str_replace(
            ['{obligation_title}', '{days_remaining}', '{obligation_no}', '{priority}', '{risk_level}'],
            [$obligation->title, abs($daysRemaining), $obligation->obligation_no, $obligation->priority, $obligation->risk_level],
            $subject
        );
    }

    public function buildMessage(Obligation $obligation, NotificationRule $rule): string
    {
        $daysRemaining = now()->startOfDay()->diffInDays($obligation->expiry_date, false);
        $message = $rule->message_template ?? 'Obligation {obligation_title} ({obligation_no}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.';

        return str_replace(
            ['{obligation_title}', '{days_remaining}', '{obligation_no}', '{priority}', '{risk_level}', '{expiry_date}', '{owner_name}', '{department_name}'],
            [$obligation->title, abs($daysRemaining), $obligation->obligation_no, $obligation->priority, $obligation->risk_level, $obligation->expiry_date->format('Y-m-d'), $obligation->owner?->name ?? 'Unassigned', $obligation->department?->department_name ?? 'N/A'],
            $message
        );
    }

    public function getRecipient(Obligation $obligation, string $recipientType): ?User
    {
        return match ($recipientType) {
            'OWNER' => $obligation->owner,
            'BACKUP_OWNER' => $obligation->backupUser,
            'REVIEWER' => $obligation->reviewer,
            'APPROVER' => $obligation->approver,
            'MANAGER' => $obligation->owner ?? $obligation->department?->headOfDepartment?->user,
            'DEPARTMENT_HEAD' => $obligation->department?->headOfDepartment?->user,
            default => null,
        };
    }

    public function isAlreadySent(Obligation $obligation, NotificationRule $rule, ?User $recipient): bool
    {
        return NotificationLog::where('obligation_id', $obligation->id)
            ->where('notification_rule_id', $rule->id)
            ->where('user_id', $recipient?->id)
            ->where('status', 'SENT')
            ->whereDate('scheduled_at', now()->toDateString())
            ->exists();
    }
}
