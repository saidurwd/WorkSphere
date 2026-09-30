<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\ActionItemCompleted;
use Modules\Meetings\Jobs\SendActionCompletedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendActionCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ActionItemCompleted $event): void
    {
        SendActionCompletedJob::dispatch($event->actionItem);
    }
}
