<?php

namespace App\Listeners;

use App\Events\TaskUpdated;
use App\Jobs\SendTaskUpdatedJob;
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
