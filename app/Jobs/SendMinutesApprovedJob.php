<?php

namespace App\Jobs;

use App\Mail\MinutesApproved;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMinutesApprovedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Meeting $meeting) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $recipients = [$this->meeting->organizer_id, $this->meeting->minutes_prepared_by];
        $users = User::whereIn('id', array_filter($recipients))->get();

        foreach ($users as $user) {
            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new MinutesApproved($this->meeting, $user));

                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'minutes_approved',
                        'SENT',
                        'Minutes Approved: '.$this->meeting->title,
                        'Meeting minutes have been approved: '.$this->meeting->title,
                    );
                } catch (\Throwable $e) {
                    $notificationService->log(
                        $this->meeting,
                        null,
                        $user,
                        'EMAIL',
                        'minutes_approved',
                        'FAILED',
                        'Minutes Approved: '.$this->meeting->title,
                        'Meeting minutes have been approved: '.$this->meeting->title,
                        $e->getMessage(),
                    );
                }
            }
        }
    }
}
