<?php

namespace App\Jobs;

use App\Mail\ActionCompleted;
use App\Models\MeetingActionItem;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendActionCompletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MeetingActionItem $actionItem) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $creator = $this->actionItem->meeting->organizer;

        if ($creator && $creator->email && $creator->id !== $this->actionItem->assigned_to) {
            try {
                Mail::to($creator->email)->send(new ActionCompleted($this->actionItem, $creator));

                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $creator,
                    'EMAIL',
                    'action_completed',
                    'SENT',
                    'Action Completed: '.$this->actionItem->title,
                    'Action item has been marked as completed: '.$this->actionItem->title,
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->actionItem->meeting,
                    $this->actionItem,
                    $creator,
                    'EMAIL',
                    'action_completed',
                    'FAILED',
                    'Action Completed: '.$this->actionItem->title,
                    'Action item has been marked as completed: '.$this->actionItem->title,
                    $e->getMessage(),
                );
            }
        }
    }
}
