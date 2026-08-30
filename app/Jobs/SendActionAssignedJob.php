<?php

namespace App\Jobs;

use App\Mail\ActionAssigned;
use App\Models\MeetingActionItem;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendActionAssignedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MeetingActionItem $actionItem) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $assignee = $this->actionItem->assignedTo;
        $creator = $this->actionItem->meeting->organizer;

        if ($assignee && $assignee->email && $assignee->id !== $creator?->id) {
            try {
                Mail::to($assignee->email)->send(new ActionAssigned($this->actionItem, $assignee, $creator));

                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $assignee,
                    'EMAIL',
                    'action_assigned',
                    'SENT',
                    'Action Assigned: '.$this->actionItem->title,
                    'You have been assigned to action item: '.$this->actionItem->title,
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $assignee,
                    'EMAIL',
                    'action_assigned',
                    'FAILED',
                    'Action Assigned: '.$this->actionItem->title,
                    'You have been assigned to action item: '.$this->actionItem->title,
                    $e->getMessage(),
                );
            }
        }
    }
}
