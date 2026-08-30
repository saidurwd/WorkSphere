<?php

namespace App\Mail;

use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class ActionCompleted extends MailMessage
{
    public function __construct(public MeetingActionItem $actionItem, public User $user) {}

    public function build()
    {
        return $this->subject('Action Completed: '.$this->actionItem->title)
            ->view('emails.meetings.action_completed', [
                'actionItem' => $this->actionItem,
                'user' => $this->user,
            ]);
    }
}
