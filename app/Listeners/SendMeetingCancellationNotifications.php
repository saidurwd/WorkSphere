<?php

namespace App\Listeners;

use App\Events\MeetingCancelled;
use App\Jobs\SendMeetingCancellationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMeetingCancellationNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MeetingCancelled $event): void
    {
        SendMeetingCancellationJob::dispatch($event->meeting);
    }
}
