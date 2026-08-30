<?php

namespace App\Listeners;

use App\Events\ActionItemCompleted;
use App\Jobs\SendActionCompletedJob;
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
