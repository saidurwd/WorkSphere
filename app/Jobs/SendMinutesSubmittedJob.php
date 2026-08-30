<?php

namespace App\Jobs;

use App\Mail\MinutesSubmitted;
use App\Models\Meeting;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMinutesSubmittedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Meeting $meeting) {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $approvers = $this->meeting->minutesApprovals()->where('status', 'pending')->get();

        foreach ($approvers as $approval) {
            $approver = $approval->approver;
            if ($approver && $approver->email) {
                try {
                    Mail::to($approver->email)->send(new MinutesSubmitted($this->meeting, $approver));

                    $notificationService->log(
                        $this->meeting,
                        null,
                        $approver,
                        'EMAIL',
                        'minutes_submitted',
                        'SENT',
                        'Minutes Submitted: '.$this->meeting->title,
                        'Minutes have been submitted for approval: '.$this->meeting->title,
                    );
                } catch (\Throwable $e) {
                    $notificationService->log(
                        $this->meeting,
                        null,
                        $approver,
                        'EMAIL',
                        'minutes_submitted',
                        'FAILED',
                        'Minutes Submitted: '.$this->meeting->title,
                        'Minutes have been submitted for approval: '.$this->meeting->title,
                        $e->getMessage(),
                    );
                }
            }
        }
    }
}
