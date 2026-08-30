<?php

namespace App\Services;

use App\Mail\ObligationReminder;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\Obligation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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
        ]);

        try {
            if ($rule->channel === 'EMAIL' && $recipient?->email) {
                Mail::to($recipient->email)->send(new ObligationReminder($obligation, $rule, $recipient));
            }

            $log->update([
                'status' => 'SENT',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'retry_count' => $log->retry_count + 1,
            ]);

            return false;
        }
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
