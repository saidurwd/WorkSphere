<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MeetingService
{
    public function __construct(private MeetingNumberService $numberService) {}

    public function create(array $data): Meeting
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['meeting_no'])) {
                $data['meeting_no'] = $this->numberService->generate(new Meeting([
                    'meeting_date' => $data['meeting_date'] ?? now(),
                ]));
            }

            $meeting = new Meeting($data);
            $meeting->created_by = Auth::id();
            $meeting->save();

            if (isset($data['participants']) && is_array($data['participants'])) {
                foreach ($data['participants'] as $participantData) {
                    $meeting->participants()->create($participantData);
                }
            }

            if (isset($data['agendas']) && is_array($data['agendas'])) {
                foreach ($data['agendas'] as $index => $agendaData) {
                    $agendaData['sort_order'] = $index + 1;
                    $meeting->agendas()->create($agendaData);
                }
            }

            $this->createVersion($meeting, 'Initial Draft', Auth::id());

            return $meeting->load(['type', 'organizer', 'chairperson', 'department', 'participants.user', 'agendas']);
        });
    }

    public function update(Meeting $meeting, array $data): Meeting
    {
        return DB::transaction(function () use ($meeting, $data) {
            $meeting->update($data);

            if (array_key_exists('participants', $data)) {
                $meeting->participants()->delete();
                foreach ($data['participants'] as $participantData) {
                    $meeting->participants()->create($participantData);
                }
            }

            return $meeting->load(['type', 'organizer', 'chairperson', 'department', 'participants.user', 'agendas']);
        });
    }

    public function start(Meeting $meeting): Meeting
    {
        $meeting->update(['status' => 'in_progress']);

        return $meeting->fresh();
    }

    public function complete(Meeting $meeting): Meeting
    {
        $meeting->update(['status' => 'completed']);

        return $meeting->fresh();
    }

    public function cancel(Meeting $meeting): Meeting
    {
        $meeting->update(['status' => 'cancelled']);

        return $meeting->fresh();
    }

    public function createVersion(Meeting $meeting, string $summary, ?int $userId = null): MeetingVersion
    {
        $latestVersion = $meeting->versions()->max('version_no') ?? 0;

        return $meeting->versions()->create([
            'version_no' => $latestVersion + 1,
            'snapshot_data' => $meeting->load([
                'participants.user',
                'agendas.discussions',
                'decisions',
                'actionItems',
                'attachments',
            ])->toArray(),
            'change_summary' => $summary,
            'created_by' => $userId ?? Auth::id(),
        ]);
    }
}
