<?php

namespace App\Listeners;

use App\Events\MeetingUpdated;
use App\Jobs\SendMeetingUpdateJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMeetingUpdateNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MeetingUpdated $event): void
    {
        SendMeetingUpdateJob::dispatch($event->meeting);
    }
}
