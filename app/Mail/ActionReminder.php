<?php

namespace App\Mail;

use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class ActionReminder extends MailMessage
{
    public function __construct(public MeetingActionItem $actionItem, public User $user) {}

    public function build()
    {
        return $this->subject('Action Reminder: '.$this->actionItem->title)
            ->view('emails.meetings.action_reminder', [
                'actionItem' => $this->actionItem,
                'user' => $this->user,
            ]);
    }
}
