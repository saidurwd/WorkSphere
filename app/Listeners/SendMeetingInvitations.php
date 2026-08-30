<?php

namespace App\Listeners;

use App\Events\MeetingCreated;
use App\Jobs\SendMeetingInvitationJob;
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
