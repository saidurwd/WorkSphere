<?php

namespace App\Jobs;

use App\Mail\ActionOverdue;
use App\Models\MeetingActionItem;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendActionOverdueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MeetingActionItem $actionItem) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $assignee = $this->actionItem->assignedTo;

        if ($assignee && $assignee->email) {
            try {
                Mail::to($assignee->email)->send(new ActionOverdue($this->actionItem, $assignee));

                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $assignee,
                    'EMAIL',
                    'action_overdue',
                    'SENT',
                    'Action Overdue: '.$this->actionItem->title,
                    'Overdue: Action item "'.$this->actionItem->title.'" is past its due date.',
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $assignee,
                    'EMAIL',
                    'action_overdue',
                    'FAILED',
                    'Action Overdue: '.$this->actionItem->title,
                    'Overdue: Action item "'.$this->actionItem->title.'" is past its due date.',
                    $e->getMessage(),
                );
            }
        }
    }
}
