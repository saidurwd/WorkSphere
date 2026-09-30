<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\MinutesPublished;
use Modules\Meetings\Jobs\SendMinutesPublishedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMinutesPublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MinutesPublished $event): void
    {
        SendMinutesPublishedJob::dispatch($event->meeting);
    }
}
