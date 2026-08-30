<?php

namespace App\Events;

use App\Models\MeetingActionItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionItemAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public MeetingActionItem $actionItem) {}
}
