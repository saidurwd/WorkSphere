<?php

namespace Modules\Meetings\Services;

use App\Services\RecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingRecurrence;

/**
 * Meeting recurrence — GAP-049.
 *
 * The date arithmetic now lives in the shared `App\Services\RecurrenceService`,
 * which the To-Do module is the first consumer of. Three copies of the same
 * `match` over frequency is three places for an off-by-one to hide; one is
 * somewhere to fix it.
 *
 * The month step changed as a result. `addMonths()` on 31 January gives 3 March
 * (or 2 March), silently skipping February; the shared service uses
 * `addMonthsNoOverflow()`, so the occurrence lands on the last day of the short
 * month instead. That is a behaviour change and it is the correct one.
 *
 * Everything else about this class is unchanged: the same meeting is cloned, the
 * same participants and agenda items are copied, and the same transaction wraps it.
 */
class MeetingRecurrenceService
{
    public function __construct(private readonly RecurrenceService $recurrence) {}

    /**
     * The occurrences a recurrence rule produces, bounded by its end date, its
     * maximum, and a one-year horizon.
     *
     * @return list<CarbonImmutable>
     */
    public function generateOccurrences(MeetingRecurrence $recurrence): array
    {
        $rule = $this->toRule($recurrence);
        $horizon = CarbonImmutable::parse(now())->addYear();

        $occurrences = [];
        $cursor = CarbonImmutable::parse($recurrence->start_date)->startOfDay();
        $number = 0;

        // The guard bounds a rule that would otherwise spin: max_occurrences and
        // end_date are both respected by the shared service, but a rule with
        // neither still needs a hard stop.
        for ($i = 0; $i < 500; $i++) {
            $number++;

            if ($number > 1 && $cursor->greaterThan($horizon)) {
                break;
            }

            $occurrences[] = $cursor;

            $next = $this->recurrence->nextOccurrence($rule, $cursor, $number);

            if ($next === null) {
                break;
            }

            $cursor = $next;
        }

        return $occurrences;
    }

    public function createNextMeeting(Meeting $parentMeeting): ?Meeting
    {
        $recurrence = $parentMeeting->recurrence()->where('is_active', true)->first();

        if (! $recurrence) {
            return null;
        }

        $occurrences = $this->generateOccurrences($recurrence);
        $nextDate = $occurrences[1] ?? null;

        if (! $nextDate) {
            return null;
        }

        $newMeeting = $parentMeeting->replicate();
        $newMeeting->meeting_date = $nextDate;
        $newMeeting->status = 'scheduled';
        $newMeeting->minutes_status = 'draft';
        $newMeeting->meeting_no = null;
        $newMeeting->created_by = auth()->id();

        DB::transaction(function () use ($newMeeting, $parentMeeting): void {
            $newMeeting->save();

            foreach ($parentMeeting->participants as $participant) {
                $newMeeting->participants()->create($participant->only([
                    'user_id', 'participant_type', 'attendance_status', 'remarks',
                ]));
            }

            foreach ($parentMeeting->agendas as $agenda) {
                $newMeeting->agendas()->create($agenda->only([
                    'agenda_no', 'title', 'description', 'presented_by', 'estimated_minutes', 'status', 'sort_order',
                ]));
            }
        });

        return $newMeeting->load(['type', 'organizer', 'chairperson', 'participants.user', 'agendas']);
    }

    /**
     * `meeting_recurrences` in the shape the shared service expects (§5.2).
     *
     * @return array<string, mixed>
     */
    protected function toRule(MeetingRecurrence $recurrence): array
    {
        return [
            'frequency' => $recurrence->recurrence_type,
            'interval' => (int) ($recurrence->recurrence_interval ?: 1),
            'start_date' => $recurrence->start_date,
            'end_date' => $recurrence->end_date,
            'max_occurrences' => $recurrence->occurrences,
        ];
    }
}
