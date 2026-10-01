<?php

namespace App\Enums;

/**
 * Notification type vocabulary for the To-Do module.
 *
 * Two jobs depend on these strings being stable:
 *
 * - `notification_preferences.notification_type` — a user's opt-out is keyed on
 *   the exact value, so renaming one silently resets that user's choice.
 * - `notification_logs.notification_type` and the `dedupe_key` prefix — a
 *   rename makes every previously-recorded send look like a new one.
 *
 * Backed by the event name in lower_snake_case so `event_created` and the
 * dedupe key `todo.created:…` cannot drift apart.
 */
enum NotificationType: string
{
    case TodoCreated = 'todo.created';
    case TodoAssigned = 'todo.assigned';
    case TodoReassigned = 'todo.reassigned';
    case TodoCompleted = 'todo.completed';
    case TodoReopened = 'todo.reopened';
    case TodoOverdue = 'todo.overdue';
    case TodoDueSoon = 'todo.due_soon';
    case TodoCommented = 'todo.commented';
    case TodoMentioned = 'todo.mentioned';
    case TodoRecurringGenerated = 'todo.recurring_generated';
    case TodoReminder = 'todo.reminder';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_map(fn (self $case): string => $case->label(), self::cases());
    }

    /**
     * The dedupe-key prefix for this type. Split out so the format is defined
     * once — §6.4 requires `{type}:{subject_id}:{discriminator}`.
     */
    public function dedupePrefix(): string
    {
        return $this->value;
    }

    /**
     * Build the idempotency key for one delivery.
     *
     * Two sends with the same key are the same send, which is what lets
     * `todos:overdue` be re-run without double-notifying.
     */
    public function dedupeKey(int $subjectId, string $discriminator = ''): string
    {
        $key = $this->dedupePrefix().':'.$subjectId;

        return $discriminator === '' ? $key : $key.':'.$discriminator;
    }

    public function label(): string
    {
        return match ($this) {
            self::TodoCreated => 'To-Do created',
            self::TodoAssigned => 'To-Do assigned',
            self::TodoReassigned => 'To-Do reassigned',
            self::TodoCompleted => 'To-Do completed',
            self::TodoReopened => 'To-Do reopened',
            self::TodoOverdue => 'To-Do overdue',
            self::TodoDueSoon => 'To-Do due soon',
            self::TodoCommented => 'New comment',
            self::TodoMentioned => 'You were mentioned',
            self::TodoRecurringGenerated => 'Next occurrence created',
            self::TodoReminder => 'Reminder',
        };
    }
}
