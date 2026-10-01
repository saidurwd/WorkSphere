<?php

namespace Modules\Obligations\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Obligations\Jobs\SendObligationEscalationJob;
use Modules\Obligations\Models\EscalationRule;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\Obligation;

/**
 * Escalation rules — GAP-031, GAP-022.
 *
 * Two changes in this phase:
 *
 * 1. **Scope.** A rule now carries an optional `department_id` / `company_id`. A
 *    rule with a NULL scope stays global, so every existing rule keeps its
 *    current behaviour rather than silently narrowing to one department.
 *
 * 2. **Queued delivery (GAP-022).** Sends are dispatched to
 *    {@see SendObligationEscalationJob} instead of running inline. This method
 *    previously did `Mail::to()->send()` inside a scheduler tick, so a slow
 *    handshake stalled the scheduler and one bad mailbox aborted the run.
 */
class EscalationService
{
    public function escalate(Obligation $obligation): void
    {
        $rules = $this->applicableRules($obligation);

        $daysRemaining = (int) now()->startOfDay()->diffInDays($obligation->expiry_date, false);

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $daysRemaining)) {
                continue;
            }

            $recipient = $this->resolveRecipient($obligation, $rule->recipient_type);

            if (! $recipient) {
                continue;
            }

            $log = $this->alreadySentToday($obligation, $rule, $recipient);

            if ($log !== null) {
                continue;
            }

            $log = $this->createLog($obligation, $rule, $recipient, $daysRemaining);

            // Queued, never inline. The log row is written here and marked SENT by
            // the job, so the record reflects what happened rather than what the
            // scheduler intended.
            SendObligationEscalationJob::dispatch(
                $obligation,
                $recipient,
                (string) $rule->channel,
                (string) $rule->escalation_level,
                (int) $log->id,
            );

            app(ObligationActivitySynchroniser::class)->record(
                $obligation,
                'ESCALATED',
                null,
                ['escalation_level' => $rule->escalation_level, 'recipient_type' => $rule->recipient_type],
                'Escalation triggered',
                null,
                request()->ip(),
                request()->userAgent(),
            );
        }
    }

    /**
     * Rules that could apply to this obligation: right type (or type-agnostic),
     * active, and in scope.
     *
     * A scoped rule matches only when the obligation's department/company EQUALS
     * the rule's. The comparison is done in SQL so a run over many obligations
     * does not load every rule and filter in PHP.
     *
     * @return Collection<int, EscalationRule>
     */
    protected function applicableRules(Obligation $obligation)
    {
        return EscalationRule::query()
            ->where(function ($query) use ($obligation) {
                $query->where('obligation_type_id', $obligation->obligation_type_id)
                    ->orWhereNull('obligation_type_id');
            })
            ->where('active', true)
            ->where(function ($query) use ($obligation) {
                // A NULL scope is global; a set scope must equal the obligation's.
                // Both dimensions are ANDed: a rule scoped to department A and
                // company B applies only to obligations matching both.
                $query->where(function (Builder $inner) use ($obligation): void {
                    $inner->whereNull('department_id');

                    if ($obligation->department_id !== null) {
                        $inner->orWhere('department_id', $obligation->department_id);
                    }
                });

                $query->where(function (Builder $inner) use ($obligation): void {
                    $inner->whereNull('company_id');

                    if ($obligation->company_id !== null) {
                        $inner->orWhere('company_id', $obligation->company_id);
                    }
                });
            })
            ->get();
    }

    protected function matches(EscalationRule $rule, int $daysRemaining): bool
    {
        if ($rule->days_before_expiry !== null && $daysRemaining >= 0 && $daysRemaining <= $rule->days_before_expiry) {
            return true;
        }

        return $rule->days_after_expiry !== null
            && $daysRemaining < 0
            && abs($daysRemaining) >= $rule->days_after_expiry;
    }

    protected function alreadySentToday(
        Obligation $obligation,
        EscalationRule $rule,
        User $recipient,
    ): ?NotificationLog {
        return NotificationLog::query()
            ->where('obligation_id', $obligation->id)
            ->where('channel', $rule->channel)
            ->where('notification_type', $rule->escalation_level)
            ->where('user_id', $recipient->id)
            ->whereDate('created_at', now()->toDateString())
            ->first();
    }

    protected function createLog(
        Obligation $obligation,
        EscalationRule $rule,
        User $recipient,
        int $daysRemaining,
    ): NotificationLog {
        return NotificationLog::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $recipient->id,
            'channel' => $rule->channel,
            'notification_type' => $rule->escalation_level,
            'scheduled_at' => now(),
            'status' => 'PENDING',
            // The dedupe key is what makes a re-run of `obligations:process` safe:
            // Phase 6 added the unique index behind it.
            'dedupe_key' => sprintf(
                'obligation.escalated:%d:%s:%s:%s',
                $obligation->id,
                $rule->escalation_level,
                $recipient->id,
                now()->toDateString(),
            ),
            'subject' => '[ESCALATION] '.$obligation->title.' requires attention',
            'message' => 'Obligation '.$obligation->obligation_no.' ('.$obligation->title.') has been escalated. Days remaining: '.abs($daysRemaining).'.',
        ]);
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
