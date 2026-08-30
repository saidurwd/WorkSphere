<?php

namespace App\Listeners;

use App\Events\MinutesApproved;
use App\Jobs\SendMinutesApprovedJob;
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
