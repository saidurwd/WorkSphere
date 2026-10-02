<?php

namespace Modules\Todos\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The single To-Do notification, covering every type.
 *
 * One class rather than one per type on purpose: the payload is identical
 * ("you have a To-Do called X and here is why"), and eleven near-identical
 * classes would be eleven places for a channel bug to hide. The type is carried
 * on the instance so `toArray()` and `toMail()` can differ.
 *
 * Mail bodies carry the To-Do title only — never its description, which may hold
 * information the recipient is not cleared to see, and which would also end up
 * in log lines if a mail transport ever failed loudly.
 */
class TodoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $todoId,
        public readonly string $title,
        public readonly NotificationType $type,
        public readonly string $actorName = '',
        /**
         * The channels `TodoNotificationService` resolved for this recipient, after
         * consulting `notification_preferences`.
         *
         * This argument exists because `via()` used to hard-code both channels.
         * The service already computed a per-channel answer — and recorded the
         * channels it chose in the `notification_logs` row — but the notification
         * then ignored it and delivered on both. So a user who opted out of email
         * was still emailed: `notification_preferences` looked like it worked,
         * because the opt-OUT case was never distinguishable from "opted in to
         * something".
         *
         * Empty means "no preference was applied", which is the right default for a
         * notification constructed directly rather than through the service.
         *
         * @var list<string>
         */
        public readonly array $channels = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels !== []
            ? $this->channels
            : [NotificationChannel::Database->value, NotificationChannel::Mail->value];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'todo_id' => $this->todoId,
            'title' => $this->title,
            'type' => $this->type->value,
            'message' => $this->message(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->type->label().': '.$this->title)
            ->line($this->message());

        if ($this->actorName !== '') {
            $mail->line('From: '.$this->actorName);
        }

        return $mail->action('Open To-Do', route('todos.show', $this->todoId));
    }

    /**
     * Human-readable sentence for the notification feed. Never includes the
     * description or any other free-text payload.
     */
    public function message(): string
    {
        return match ($this->type) {
            NotificationType::TodoCreated => sprintf('"%s" was created for you.', $this->title),
            NotificationType::TodoAssigned => sprintf('"%s" was assigned to you.', $this->title),
            NotificationType::TodoReassigned => sprintf('"%s" was reassigned.', $this->title),
            NotificationType::TodoCompleted => sprintf('"%s" was completed.', $this->title),
            NotificationType::TodoReopened => sprintf('"%s" was reopened.', $this->title),
            NotificationType::TodoOverdue => sprintf('"%s" is overdue.', $this->title),
            NotificationType::TodoDueSoon => sprintf('"%s" is due shortly.', $this->title),
            NotificationType::TodoCommented => sprintf('New comment on "%s".', $this->title),
            NotificationType::TodoMentioned => sprintf('You were mentioned on "%s".', $this->title),
            NotificationType::TodoRecurringGenerated => sprintf('The next "%s" is ready.', $this->title),
            NotificationType::TodoReminder => sprintf('Reminder: "%s".', $this->title),
        };
    }
}
