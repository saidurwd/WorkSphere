<?php

namespace App\Mail;

use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class ActionOverdue extends MailMessage
{
    public function __construct(public MeetingActionItem $actionItem, public User $user) {}

    public function build()
    {
        return $this->subject('Overdue Action: '.$this->actionItem->title)
            ->view('emails.meetings.action_overdue', [
                'actionItem' => $this->actionItem,
                'user' => $this->user,
            ]);
    }
}
