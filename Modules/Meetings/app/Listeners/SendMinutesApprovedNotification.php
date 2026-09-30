<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MinutesApproved;
use Modules\Meetings\Jobs\SendMinutesApprovedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMinutesApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MinutesApproved $event): void
    {
        SendMinutesApprovedJob::dispatch($event->meeting);
    }
}
