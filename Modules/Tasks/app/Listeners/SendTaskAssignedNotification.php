<?php

namespace Modules\Tasks\Listeners;

use Modules\Tasks\Events\TaskAssigned;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Jobs\SendTaskAssignedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTaskAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TaskCreated|TaskAssigned $event): void
    {
        if ($event->task->responsible_user_id) {
            SendTaskAssignedJob::dispatch($event->task);
        }
    }
}
