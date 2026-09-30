<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MeetingCreated;
use Modules\Meetings\Jobs\SendMeetingInvitationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMeetingInvitations implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MeetingCreated $event): void
    {
        SendMeetingInvitationJob::dispatch($event->meeting);
    }
}
