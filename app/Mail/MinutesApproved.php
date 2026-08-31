<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MinutesApproved extends Mailable
{
    public function __construct(public Meeting $meeting, public User $user)
    {
        $this->subject('Minutes Approved: '.$this->meeting->title)
            ->view('emails.meetings.minutes_approved', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
