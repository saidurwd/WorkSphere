<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class MeetingInvitation extends MailMessage
{
    public function __construct(public Meeting $meeting, public User $user) {}

    public function build()
    {
        return $this->subject('Meeting Invitation: '.$this->meeting->title)
            ->view('emails.meetings.invitation', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
