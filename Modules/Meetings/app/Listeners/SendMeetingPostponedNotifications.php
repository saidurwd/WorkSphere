<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MeetingPostponed;
use Modules\Meetings\Jobs\SendMeetingUpdateJob;
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
