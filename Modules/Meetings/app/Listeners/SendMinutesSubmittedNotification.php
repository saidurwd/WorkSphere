<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MinutesSubmitted;
use Modules\Meetings\Jobs\SendMinutesSubmittedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMinutesSubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MinutesSubmitted $event): void
    {
        SendMinutesSubmittedJob::dispatch($event->meeting);
    }
}
