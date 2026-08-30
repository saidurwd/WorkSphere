<?php

namespace App\Jobs;

use App\Mail\MinutesReturned;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMinutesReturnedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Meeting $meeting, public string $comments = '') {}

    public function handle(MeetingNotificationService $notificationService): void
    {
        $preparer = $this->meeting->minutes_prepared_by ? User::find($this->meeting->minutes_prepared_by) : null;

        if ($preparer && $preparer->email) {
            try {
                Mail::to($preparer->email)->send(new MinutesReturned($this->meeting, $preparer, $this->comments));

                $notificationService->log(
                    $this->meeting,
                    null,
                    $preparer,
                    'EMAIL',
                    'minutes_returned',
                    'SENT',
                    'Minutes Returned: '.$this->meeting->title,
                    'Meeting minutes have been returned for revision: '.$this->meeting->title,
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->meeting,
                    null,
                    $preparer,
                    'EMAIL',
                    'minutes_returned',
                    'FAILED',
                    'Minutes Returned: '.$this->meeting->title,
                    'Meeting minutes have been returned for revision: '.$this->meeting->title,
                    $e->getMessage(),
                );
            }
        }
    }
}
