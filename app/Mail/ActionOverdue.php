<?php

namespace App\Mail;

use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Mail\Mailable;

class ActionOverdue extends Mailable
{
    public function __construct(public MeetingActionItem $actionItem, public User $user)
    {
        $this->subject('Overdue Action: '.$this->actionItem->title)
            ->view('emails.meetings.action_overdue', [
                'actionItem' => $this->actionItem,
                'user' => $this->user,
            ]);
    }
}
