<?php

namespace App\Listeners;

use App\Events\MinutesReturned;
use App\Jobs\SendMinutesReturnedJob;
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
