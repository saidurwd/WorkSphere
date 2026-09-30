<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MinutesReturned;
use Modules\Meetings\Jobs\SendMinutesReturnedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMinutesReturnedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MinutesReturned $event): void
    {
        SendMinutesReturnedJob::dispatch($event->meeting, $event->comments);
    }
}
