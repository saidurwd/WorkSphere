<?php

namespace App\Jobs;

use App\Mail\MeetingCancelled;
use App\Models\Meeting;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMeetingCancellationJob implements ShouldQueue
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
                    Mail::to($user->email)->send(new MeetingCancelled($this->meeting, $user));

                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'meeting_cancelled',
                        'SENT',
                        'Meeting Cancelled: '.$this->meeting->title,
                        'Meeting has been cancelled: '.$this->meeting->title,
                    );
                } catch (\Throwable $e) {
                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'meeting_cancelled',
                        'FAILED',
                        'Meeting Cancelled: '.$this->meeting->title,
                        'Meeting has been cancelled: '.$this->meeting->title,
                        $e->getMessage(),
                    );
                }
            }
        }
    }
}
