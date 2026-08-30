<?php

namespace App\Listeners;

use App\Events\MinutesPublished;
use App\Jobs\SendMinutesPublishedJob;
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
