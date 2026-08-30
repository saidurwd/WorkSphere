<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskCompleted extends Mailable
{
    use SerializesModels;

    public function __construct(public Task $task, public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Task Completed: '.$this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tasks.completed',
        );
    }
}
