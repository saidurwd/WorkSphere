<?php

namespace Modules\Meetings\Mail;

use Modules\Meetings\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MinutesPublished extends Mailable
{
    public function __construct(public Meeting $meeting, public User $user)
    {
        $this->subject('Minutes Published: '.$this->meeting->title)
            ->view('emails.meetings.minutes_published', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
