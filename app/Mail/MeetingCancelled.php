<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class MeetingCancelled extends MailMessage
{
    public function __construct(public Meeting $meeting, public User $user) {}

    public function build()
    {
        return $this->subject('Meeting Cancelled: '.$this->meeting->title)
            ->view('emails.meetings.cancelled', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
