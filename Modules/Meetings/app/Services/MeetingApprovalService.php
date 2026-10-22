<?php

namespace Modules\Meetings\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingMinutesApproval;

/**
 * Minutes approval with named approvers.
 *
 * Submitting for approval can name the people who must sign the minutes off. Each
 * becomes a pending step; the minutes are approved once every step is, and any one
 * of them returning the minutes sends them back to the preparer with the reason
 * kept on that step. Minutes submitted with no named approvers carry no steps and
 * go through {@see MeetingMinutesService} instead.
 */
class MeetingApprovalService
{
    /**
     * @param  list<int>  $approverIds
     */
    public function submitForApproval(Meeting $meeting, array $approverIds = []): Meeting
    {
        return DB::transaction(function () use ($meeting, $approverIds): Meeting {
            // A fresh submission replaces any steps still waiting from an earlier
            // one; steps already decided stay as the history.
            $meeting->minutesApprovals()->where('status', 'pending')->delete();

            $step = (int) $meeting->minutesApprovals()->max('step_no');

            foreach (array_values(array_unique($approverIds)) as $approverId) {
                $meeting->minutesApprovals()->create([
                    'step_no' => ++$step,
                    'approver_id' => $approverId,
                    'status' => 'pending',
                ]);
            }

            $meeting->update(['minutes_status' => 'submitted']);

            return $meeting->fresh();
        });
    }

    /**
     * Record the current user's approval. The minutes become approved only when no
     * step is left pending; until then they are under review.
     */
    public function approve(Meeting $meeting, ?string $comments = null): MeetingMinutesApproval
    {
        return DB::transaction(function () use ($meeting, $comments): MeetingMinutesApproval {
            $approval = $this->pendingFor($meeting, auth()->user())->firstOrFail();

            $approval->update([
                'status' => 'approved',
                'comments' => $comments,
                'action_at' => now(),
            ]);

            if ($meeting->minutesApprovals()->where('status', 'pending')->exists()) {
                $meeting->update(['minutes_status' => 'under_review']);
            } else {
                $meeting->update([
                    'minutes_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

            return $approval->refresh();
        });
    }

    /**
     * Return the minutes to the preparer, keeping the reason on the step. Steps
     * still pending are withdrawn, because the minutes will be resubmitted.
     */
    public function reject(Meeting $meeting, string $comments): MeetingMinutesApproval
    {
        return DB::transaction(function () use ($meeting, $comments): MeetingMinutesApproval {
            $approval = $this->pendingFor($meeting, auth()->user())->firstOrFail();

            $approval->update([
                'status' => 'returned',
                'comments' => $comments,
                'action_at' => now(),
            ]);

            $meeting->minutesApprovals()->where('status', 'pending')->delete();

            $meeting->update(['minutes_status' => 'prepared']);

            return $approval->refresh();
        });
    }

    /**
     * Whether the minutes are waiting on named approvers at all.
     */
    public function hasSteps(Meeting $meeting): bool
    {
        return $meeting->minutesApprovals()->where('status', 'pending')->exists();
    }

    public function isWaitingOn(Meeting $meeting, ?User $user): bool
    {
        return $user !== null && $this->pendingFor($meeting, $user)->exists();
    }

    protected function pendingFor(Meeting $meeting, ?User $user)
    {
        return $meeting->minutesApprovals()
            ->where('approver_id', $user?->getKey())
            ->where('status', 'pending')
            ->orderBy('step_no');
    }
}
