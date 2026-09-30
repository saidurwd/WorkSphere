<?php

namespace Modules\Meetings\Mail;

use Modules\Meetings\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MeetingInvitation extends Mailable
{
    public function __construct(public Meeting $meeting, public User $user)
    {
        $this->subject('Meeting Invitation: '.$this->meeting->title)
            ->view('emails.meetings.invitation', [
                'meeting' => $this->meeting,
                'user' => $this->user,
            ]);
    }
}
