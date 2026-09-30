<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MeetingUpdated;
use Modules\Meetings\Jobs\SendMeetingUpdateJob;
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
