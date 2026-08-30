<?php

namespace App\Jobs;

use App\Mail\ActionReminder;
use App\Models\MeetingActionItem;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendActionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MeetingActionItem $actionItem) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $assignee = $this->actionItem->assignedTo;

        if ($assignee && $assignee->email) {
            try {
                Mail::to($assignee->email)->send(new ActionReminder($this->actionItem, $assignee));

                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $assignee,
                    'EMAIL',
                    'action_reminder',
                    'SENT',
                    'Action Reminder: '.$this->actionItem->title,
                    'Reminder: Action item "'.$this->actionItem->title.'" is due soon.',
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $assignee,
                    'EMAIL',
                    'action_reminder',
                    'FAILED',
                    'Action Reminder: '.$this->actionItem->title,
                    'Reminder: Action item "'.$this->actionItem->title.'" is due soon.',
                    $e->getMessage(),
                );
            }
        }
    }
}
