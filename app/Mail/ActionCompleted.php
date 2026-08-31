<?php

namespace App\Mail;

use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Mail\Mailable;

class ActionCompleted extends Mailable
{
    public function __construct(public MeetingActionItem $actionItem, public User $user)
    {
        $this->subject('Action Completed: '.$this->actionItem->title)
            ->view('emails.meetings.action_completed', [
                'actionItem' => $this->actionItem,
                'user' => $this->user,
            ]);
    }
}
