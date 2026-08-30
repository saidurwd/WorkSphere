<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingMinutesApproval;
use Illuminate\Support\Facades\DB;

class MeetingApprovalService
{
    public function submitForApproval(Meeting $meeting): Meeting
    {
        $meeting->update([
            'minutes_status' => 'submitted',
        ]);

        return $meeting->fresh();
    }

    public function approve(Meeting $meeting, ?string $comments = null): MeetingMinutesApproval
    {
        DB::transaction(function () use ($meeting, $comments) {
            $approval = $meeting->minutesApprovals()->where('approver_id', auth()->id())
                ->where('status', 'pending')
                ->firstOrFail();

            $approval->update([
                'status' => 'approved',
                'comments' => $comments,
                'action_at' => now(),
            ]);

            $meeting->update([
                'minutes_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return $meeting->minutesApprovals()->where('approver_id', auth()->id())->first();
    }

    public function reject(Meeting $meeting, string $comments): MeetingMinutesApproval
    {
        return DB::transaction(function () use ($meeting, $comments) {
            $approval = $meeting->minutesApprovals()->where('approver_id', auth()->id())
                ->where('status', 'pending')
                ->firstOrFail();

            $approval->update([
                'status' => 'returned',
                'comments' => $comments,
                'action_at' => now(),
            ]);

            $meeting->update([
                'minutes_status' => 'prepared',
            ]);

            return $approval;
        });
    }
}
