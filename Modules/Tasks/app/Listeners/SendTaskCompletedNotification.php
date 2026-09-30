<?php

namespace Modules\Tasks\Listeners;

use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Jobs\SendTaskCompletedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTaskCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TaskCompleted $event): void
    {
        SendTaskCompletedJob::dispatch($event->task);
    }
}
