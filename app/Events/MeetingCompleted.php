<?php

namespace App\Events;

use App\Models\Meeting;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Meeting $meeting) {}
}
