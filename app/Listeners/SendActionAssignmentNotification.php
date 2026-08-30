<?php

namespace App\Listeners;

use App\Events\ActionItemCreated;
use App\Jobs\SendActionAssignedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendActionAssignmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ActionItemCreated $event): void
    {
        if ($event->actionItem->assigned_to) {
            SendActionAssignedJob::dispatch($event->actionItem);
        }
    }
}
