<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MinutesReturned extends Mailable
{
    public function __construct(public Meeting $meeting, public User $user, public string $comments = '')
    {
        $this->subject('Minutes Returned: '.$this->meeting->title)
            ->view('emails.meetings.minutes_returned', [
                'meeting' => $this->meeting,
                'user' => $this->user,
                'comments' => $this->comments,
            ]);
    }
}
