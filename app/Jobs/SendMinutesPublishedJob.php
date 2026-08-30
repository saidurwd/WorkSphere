<?php

namespace App\Jobs;

use App\Mail\MinutesPublished;
use App\Models\Meeting;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMinutesPublishedJob implements ShouldQueue
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
                    Mail::to($user->email)->send(new MinutesPublished($this->meeting, $user));

                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'minutes_published',
                        'SENT',
                        'Minutes Published: '.$this->meeting->title,
                        'Meeting minutes have been published: '.$this->meeting->title,
                    );
                } catch (\Throwable $e) {
                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'minutes_published',
                        'FAILED',
                        'Minutes Published: '.$this->meeting->title,
                        'Meeting minutes have been published: '.$this->meeting->title,
                        $e->getMessage(),
                    );
                }
            }
        }
    }
}
