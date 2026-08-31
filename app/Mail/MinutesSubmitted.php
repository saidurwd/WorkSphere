<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Mail\Mailable;

class MinutesSubmitted extends Mailable
{
    public function __construct(public Meeting $meeting, public User $approver)
    {
        $this->subject('Minutes Submitted for Approval: '.$this->meeting->title)
            ->view('emails.meetings.minutes_submitted', [
                'meeting' => $this->meeting,
                'approver' => $this->approver,
            ]);
    }
}
