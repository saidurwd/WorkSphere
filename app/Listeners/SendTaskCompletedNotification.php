<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Jobs\SendTaskCompletedJob;
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
