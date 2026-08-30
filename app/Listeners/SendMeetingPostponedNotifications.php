<?php

namespace App\Listeners;

use App\Events\MeetingPostponed;
use App\Jobs\SendMeetingUpdateJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMeetingPostponedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MeetingPostponed $event): void
    {
        SendMeetingUpdateJob::dispatch($event->meeting);
    }
}
