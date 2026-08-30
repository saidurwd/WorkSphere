<?php

namespace App\Listeners;

use App\Events\MinutesSubmitted;
use App\Jobs\SendMinutesSubmittedJob;
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
