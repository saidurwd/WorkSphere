<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\ActionItemCreated;
use Modules\Meetings\Jobs\SendActionAssignedJob;
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
