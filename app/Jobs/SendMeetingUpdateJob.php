<?php

namespace App\Jobs;

use App\Mail\MeetingUpdated;
use App\Models\Meeting;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMeetingUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Meeting $meeting) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $participants = $this->meeting->participants()->whereNotNull('user_id')->get();

        foreach ($participants as $participant) {
            $user = $participant->user;
            if ($user && $user->email) {
                try {
                    Mail::to($user->email)->send(new MeetingUpdated($this->meeting, $user));

                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'meeting_updated',
                        'SENT',
                        'Meeting Updated: '.$this->meeting->title,
                        'Meeting details have been updated: '.$this->meeting->title,
                    );
                } catch (\Throwable $e) {
                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'meeting_updated',
                        'FAILED',
                        'Meeting Updated: '.$this->meeting->title,
                        'Meeting details have been updated: '.$this->meeting->title,
                        $e->getMessage(),
                    );
                }
            }
        }
    }
}
