<?php

namespace App\Mail;

use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Mail\MailMessage;

class ActionAssigned extends MailMessage
{
    public function __construct(public MeetingActionItem $actionItem, public User $assignee, public ?User $assigner = null) {}

    public function build()
    {
        return $this->subject('Action Assigned: '.$this->actionItem->title)
            ->view('emails.meetings.action_assigned', [
                'actionItem' => $this->actionItem,
                'assignee' => $this->assignee,
                'assigner' => $this->assigner,
            ]);
    }
}
