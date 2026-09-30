<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Jobs\SendMeetingCancellationJob;
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
