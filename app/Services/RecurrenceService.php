<?php

namespace App\Services;

use App\Enums\RecurrenceFrequency;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Shared RRULE arithmetic — TODO-MODULE-SPECIFICATION.md §5.4.
 *
 * Lives in `app/Services/` rather than inside the To-Do module because the
 * To-Do module is only its first consumer: `meeting_recurrences` and
 * `obligations.recurrence_type` migrate onto it in Phase 8 (GAP-049). A copy per
 * module would be three places to fix the same off-by-one.
 *
 * The rule shape is the JSON of §5.2:
 *
 *   frequency, interval, by_weekday[], by_month_day, start_date, end_date,
 *   max_occurrences, skip_dates[]
 *
 * Dates are local business dates (+06:00) and this class works on
 * {@see CarbonImmutable} so a caller cannot accidentally mutate a rule that is
 * cached or shared.
 *
 * The skip list is applied by the caller, not here. Deciding whether "skip"
 * means "advance past this occurrence" or "stop the series" is a policy
 * decision; arithmetic only decides what the next date *would* be.
 */
class RecurrenceService
{
    /**
     * Compute the occurrence after `$from`.
     *
     * Returns null when the series is exhausted — past `end_date`, or
     * `max_occurrences` reached. Callers must handle null: it means "no next
     * occurrence", not "an error".
     *
     * @param  array<string, mixed>  $rule
     */
    public function nextOccurrence(array $rule, CarbonImmutable $from, int $occurrenceNumber = 1): ?CarbonImmutable
    {
        $this->assertRule($rule);

        if ($occurrenceNumber >= (int) ($rule['max_occurrences'] ?? PHP_INT_MAX)) {
            return null;
        }

        $frequency = RecurrenceFrequency::from((string) $rule['frequency']);
        $interval = max(1, (int) ($rule['interval'] ?? 1));

        $next = match ($frequency) {
            RecurrenceFrequency::Daily => $from->addDays($interval),
            RecurrenceFrequency::Weekly => $this->nextWeekly($rule, $from, $interval),
            RecurrenceFrequency::Biweekly => $this->nextWeekly($rule, $from, 2),
            RecurrenceFrequency::Monthly => $from->addMonthsNoOverflow($interval),
            RecurrenceFrequency::Quarterly => $from->addMonthsNoOverflow($interval * 3),
            RecurrenceFrequency::Yearly => $from->addYearsNoOverflow($interval),
            RecurrenceFrequency::Custom => $from->addDays($interval),
        };

        $endDate = $this->endDate($rule);

        if ($endDate !== null && $next->greaterThan($endDate)) {
            return null;
        }

        return $next;
    }

    /**
     * Advance to the occurrence after `$from`, skipping any date in
     * `skip_dates`. Used when one occurrence is deliberately bypassed but the
     * series continues.
     *
     * @param  array<string, mixed>  $rule
     * @param  int  $guard  Hard stop so a pathological rule cannot spin forever.
     */
    public function nextUnskipped(array $rule, CarbonImmutable $from, int $occurrenceNumber = 1, int $guard = 100): ?CarbonImmutable
    {
        $skipDates = $this->skipDates($rule);
        $current = $from;
        $number = $occurrenceNumber;

        for ($i = 0; $i < $guard; $i++) {
            $next = $this->nextOccurrence($rule, $current, $number);

            if ($next === null) {
                return null;
            }

            $number++;

            if (! in_array($next->toDateString(), $skipDates, true)) {
                return $next;
            }

            $current = $next;
        }

        throw new InvalidArgumentException(
            'Recurrence rule skipped past the guard limit; check skip_dates for a cycle.'
        );
    }

    /**
     * ISO-8601 weekday numbers: 1 = Monday … 7 = Sunday.
     *
     * @param  array<string, mixed>  $rule
     * @return list<int>
     */
    public function weekdays(array $rule): array
    {
        $days = $rule['by_weekday'] ?? [];

        if (! is_array($days)) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn (mixed $day): int => ((int) $day - 1) % 7 + 1,
            $days,
        )));
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return list<string>
     */
    public function skipDates(array $rule): array
    {
        $dates = $rule['skip_dates'] ?? [];

        if (! is_array($dates)) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn (mixed $date): string => CarbonImmutable::parse((string) $date)->toDateString(),
            $dates,
        )));
    }

    /**
     * Weekly rules advance to the next declared weekday inside the next
     * occurrence window. With no `by_weekday` the interval itself is the step.
     *
     * @param  array<string, mixed>  $rule
     */
    protected function nextWeekly(array $rule, CarbonImmutable $from, int $interval): CarbonImmutable
    {
        $weekdays = $this->weekdays($rule);

        if ($weekdays === []) {
            return $from->addWeeks($interval);
        }

        $candidate = $from->addWeeks($interval);

        foreach ($weekdays as $weekday) {
            $date = $candidate->startOfWeek(CarbonImmutable::MONDAY)
                ->addDays($weekday - 1);

            if ($date->greaterThan($from)) {
                return $date;
            }
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    protected function endDate(array $rule): ?CarbonImmutable
    {
        $endDate = $rule['end_date'] ?? null;

        if ($endDate === null || $endDate === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $endDate)->endOfDay();
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    protected function assertRule(array $rule): void
    {
        $frequency = $rule['frequency'] ?? null;

        if ($frequency === null) {
            throw new InvalidArgumentException('A recurrence rule needs a frequency.');
        }

        if (! RecurrenceFrequency::tryFrom((string) $frequency)) {
            throw new InvalidArgumentException(
                sprintf('Unknown recurrence frequency [%s].', (string) $frequency)
            );
        }

        if (isset($rule['interval']) && (int) $rule['interval'] < 1) {
            throw new InvalidArgumentException('Recurrence interval must be at least 1.');
        }
    }
}
