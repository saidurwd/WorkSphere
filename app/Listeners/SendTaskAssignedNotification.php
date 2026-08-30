<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Events\TaskCreated;
use App\Jobs\SendTaskAssignedJob;
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
