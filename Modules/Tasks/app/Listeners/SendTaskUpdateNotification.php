<?php

namespace Modules\Tasks\Listeners;

use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Jobs\SendTaskUpdatedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTaskUpdateNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TaskUpdated $event): void
    {
        SendTaskUpdatedJob::dispatch($event->task);
    }
}
