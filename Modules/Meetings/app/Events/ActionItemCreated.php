<?php

namespace Modules\Meetings\Events;

use Modules\Meetings\Models\MeetingActionItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionItemCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public MeetingActionItem $actionItem) {}
}
