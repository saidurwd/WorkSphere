<?php

namespace Modules\Obligations\Services;

use App\Services\RecurrenceService;
use Carbon\CarbonImmutable;
use Modules\Obligations\Models\Obligation;

/**
 * Obligation recurrence — GAP-049.
 *
 * The Obligations module stored `recurrence_type` and `recurrence_interval` on
 * every obligation and then **never used them**: renewals asked a person to type
 * the new expiry date, so a "quarterly" obligation renewed on whatever date
 * happened to be typed. The fields were decorative.
 *
 * This puts the shared `RecurrenceService` behind them, so a renewal rule now
 * computes the next expiry rather than defaulting to manual entry. The manual
 * dates stay accepted — a person overriding a rule is a legitimate case — but the
 * form can offer the computed date, and `RecurrenceTodoRequest`-style validation
 * can require a bound when a rule exists.
 */
class ObligationRecurrenceService
{
    public function __construct(private readonly RecurrenceService $recurrence) {}

    /**
     * The recurrence rule for an obligation, in the shape §5.2 specifies.
     *
     * Returns null when the obligation has no rule — which is the case for every
     * existing row, because the columns were never populated.
     *
     * @return array<string, mixed>|null
     */
    public function ruleFor(Obligation $obligation): ?array
    {
        if ($obligation->recurrence_type === null || $obligation->recurrence_type === '') {
            return null;
        }

        return [
            'frequency' => $obligation->recurrence_type,
            'interval' => (int) ($obligation->recurrence_interval ?: 1),
            'start_date' => $obligation->start_date?->toDateString(),
            'end_date' => null,
            // A bounded series: an obligation with no end date and no maximum would
            // renew for ever, and unlike a To-Do there is nobody to notice.
            'max_occurrences' => 1000,
        ];
    }

    /**
     * The next expiry after the obligation's current one.
     *
     * Returns null when there is no rule, or when the series has finished.
     */
    public function nextExpiry(Obligation $obligation): ?CarbonImmutable
    {
        $rule = $this->ruleFor($obligation);

        if ($rule === null || $obligation->expiry_date === null) {
            return null;
        }

        return $this->recurrence->nextOccurrence(
            $rule,
            CarbonImmutable::parse($obligation->expiry_date->format('Y-m-d'))->startOfDay(),
        );
    }

    /**
     * The next start date to accompany {@see nextExpiry()}, a period before it so
     * the obligation is live rather than born already expired.
     */
    public function nextStart(Obligation $obligation): ?CarbonImmutable
    {
        $expiry = $this->nextExpiry($obligation);

        if ($expiry === null) {
            return null;
        }

        $rule = $this->ruleFor($obligation);

        $interval = $rule === null ? 1 : (int) $rule['interval'];

        // The period, in days, is approximated by comparing the two ends: for the
        // frequencies in §5.2 the gap is the same each cycle.
        $current = $obligation->expiry_date && $obligation->start_date
            ? $obligation->expiry_date->diffInDays($obligation->start_date)
            : 30;

        $span = max(1, (int) $current) * max(1, $interval);

        return $expiry->subDays($span)->startOfDay();
    }

    /**
     * The renewal dates this rule implies, for populating a form.
     *
     * @return array{start: string, expiry: string}|null
     */
    public function suggestedRenewal(Obligation $obligation): ?array
    {
        $expiry = $this->nextExpiry($obligation);
        $start = $this->nextStart($obligation);

        if ($expiry === null || $start === null) {
            return null;
        }

        return [
            'start' => $start->toDateString(),
            'expiry' => $expiry->toDateString(),
        ];
    }
}
