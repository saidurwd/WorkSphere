<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MeetingCancelled extends Mailable
{
    public function __construct(public Meeting $meeting, public User $user)
    {
        $this->subject('Meeting Cancelled: '.$this->meeting->title)
            ->view('emails.meetings.cancelled', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
