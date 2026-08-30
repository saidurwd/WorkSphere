<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingNotificationLog;
use App\Models\User;

class MeetingNotificationService
{
    public function shouldNotify(User $user, string $type): bool
    {
        return true;
    }

    public function getRecipients(Meeting $meeting, string $event): array
    {
        return match ($event) {
            'created', 'updated' => $meeting->participants()->pluck('user_id')->filter()->unique()->values()->all(),
            'cancelled', 'postponed' => $meeting->participants()->pluck('user_id')->filter()->unique()->values()->all(),
            'action_assigned' => [$meeting->actionItems()->latest()->first()?->assigned_to],
            'action_reminder', 'action_overdue' => MeetingActionItem::where('meeting_id', $meeting->id)
                ->where('assigned_to', '!=', null)
                ->pluck('assigned_to')
                ->unique()
                ->values()
                ->all(),
            'minutes_submitted' => [$meeting->approved_by],
            'minutes_approved' => [$meeting->organizer_id, $meeting->minutes_prepared_by],
            'minutes_returned' => [$meeting->minutes_prepared_by],
            default => [],
        };
    }

    public function log(
        ?Meeting $meeting,
        ?MeetingActionItem $actionItem,
        ?User $user,
        string $channel,
        string $type,
        string $status,
        ?string $subject = null,
        ?string $message = null,
        ?string $error = null,
    ): MeetingNotificationLog {
        return MeetingNotificationLog::create([
            'meeting_id' => $meeting?->id,
            'action_item_id' => $actionItem?->id,
            'user_id' => $user?->id,
            'channel' => $channel,
            'notification_type' => $type,
            'scheduled_at' => now(),
            'status' => $status,
            'subject' => $subject,
            'message' => $message,
            'error_message' => $error,
        ]);
    }
}
