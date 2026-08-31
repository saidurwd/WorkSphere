<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MeetingUpdated extends Mailable
{
    public function __construct(public Meeting $meeting, public User $user)
    {
        $this->subject('Meeting Updated: '.$this->meeting->title)
            ->view('emails.meetings.updated', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
