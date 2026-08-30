<?php

namespace App\Mail;

use App\Models\NotificationRule;
use App\Models\Obligation;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ObligationReminder extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Obligation $obligation,
        public NotificationRule $rule,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $daysRemaining = now()->startOfDay()->diffInDays($this->obligation->expiry_date, false);
        $subject = '[Reminder] '.$this->obligation->title.' expires in '.abs($daysRemaining).' days';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.obligations.reminder',
        );
    }
}
