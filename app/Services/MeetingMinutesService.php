<?php

namespace App\Services;

use App\Models\Meeting;

class MeetingMinutesService
{
    public function prepare(Meeting $meeting): Meeting
    {
        $meeting->update([
            'minutes_status' => 'prepared',
            'minutes_prepared_by' => auth()->id(),
            'minutes_prepared_at' => now(),
        ]);

        return $meeting->fresh();
    }

    public function submit(Meeting $meeting): Meeting
    {
        $meeting->update([
            'minutes_status' => 'submitted',
        ]);

        return $meeting->fresh();
    }

    public function approve(Meeting $meeting): Meeting
    {
        $meeting->update([
            'minutes_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $meeting->fresh();
    }

    public function publish(Meeting $meeting): Meeting
    {
        $meeting->update([
            'minutes_status' => 'published',
            'published_at' => now(),
        ]);

        return $meeting->fresh();
    }

    public function returnMinutes(Meeting $meeting, string $comments): Meeting
    {
        $meeting->update([
            'minutes_status' => 'prepared',
        ]);

        return $meeting->fresh();
    }

    public function getMinutesData(Meeting $meeting): array
    {
        return [
            'meeting' => $meeting->load(['type', 'organizer', 'chairperson', 'department', 'participants.user']),
            'agendas' => $meeting->agendas()->with(['discussions', 'decisions', 'actionItems'])->get(),
            'decisions' => $meeting->decisions()->get(),
            'actionItems' => $meeting->actionItems()->with(['assignedTo', 'task'])->get(),
        ];
    }
}
