<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingRecurrence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MeetingRecurrenceService
{
    public function generateOccurrences(MeetingRecurrence $recurrence): array
    {
        $occurrences = [];
        $current = Carbon::parse($recurrence->start_date);
        $end = $recurrence->end_date ? Carbon::parse($recurrence->end_date) : null;
        $maxOccurrences = $recurrence->occurrences ?: PHP_INT_MAX;

        while ($current->lte($end ?? Carbon::now()->addYear())) {
            if (count($occurrences) >= $maxOccurrences) {
                break;
            }

            $occurrences[] = $current->copy();

            $current = match ($recurrence->recurrence_type) {
                'daily' => $current->addDays($recurrence->recurrence_interval),
                'weekly' => $current->addWeeks($recurrence->recurrence_interval),
                'biweekly' => $current->addWeeks(2),
                'monthly' => $current->addMonths($recurrence->recurrence_interval),
                'quarterly' => $current->addMonths(3),
                'yearly' => $current->addYear(),
                default => $current->addWeeks($recurrence->recurrence_interval),
            };
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

        DB::transaction(function () use ($newMeeting, $parentMeeting) {
            $newMeeting->save();

            foreach ($parentMeeting->participants as $participant) {
                $newMeeting->participants()->create($participant->only([
                    'user_id', 'participant_type', 'attendance_status', 'remarks',
                ]));
            }

            foreach ($parentMeeting->agendas as $agenda) {
                $newAgenda = $newMeeting->agendas()->create($agenda->only([
                    'agenda_no', 'title', 'description', 'presented_by', 'estimated_minutes', 'status', 'sort_order',
                ]));
            }
        });

        return $newMeeting->load(['type', 'organizer', 'chairperson', 'participants.user', 'agendas']);
    }
}
