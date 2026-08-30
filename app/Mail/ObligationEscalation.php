<?php

namespace App\Mail;

use App\Models\Obligation;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ObligationEscalation extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Obligation $obligation,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[ESCALATION] '.$this->obligation->title.' requires attention');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.obligations.escalation',
        );
    }
}
