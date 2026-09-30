<?php

namespace Modules\Tasks\Events;

use Modules\Tasks\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Task $task) {}
}
