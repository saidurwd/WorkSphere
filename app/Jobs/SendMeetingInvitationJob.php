<?php

namespace App\Jobs;

use App\Mail\MeetingInvitation;
use App\Models\Meeting;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMeetingInvitationJob implements ShouldQueue
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
                    Mail::to($user->email)->send(new MeetingInvitation($this->meeting, $user));

                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'meeting_invitation',
                        'SENT',
                        'Meeting Invitation: '.$this->meeting->title,
                        'You have been invited to meeting: '.$this->meeting->title,
                    );
                } catch (\Throwable $e) {
                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'meeting_invitation',
                        'FAILED',
                        'Meeting Invitation: '.$this->meeting->title,
                        'You have been invited to meeting: '.$this->meeting->title,
                        $e->getMessage(),
                    );
                }
            }
        }
    }
}
