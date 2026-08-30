<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class MinutesApproved extends MailMessage
{
    public function __construct(public Meeting $meeting, public User $user) {}

    public function build()
    {
        return $this->subject('Minutes Approved: '.$this->meeting->title)
            ->view('emails.meetings.minutes_approved', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
