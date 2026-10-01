<?php

namespace App\Enums;

/**
 * Work item status vocabulary.
 *
 * Existing vocabularies are preserved verbatim: `pending|in_progress|completed`
 * on `tasks`, `open|in_progress|on_hold|completed|cancelled` on
 * `meeting_action_items` and `scheduled|in_progress|completed|cancelled|postponed`
 * on `meetings`. Nothing is renamed, so no stored value changes meaning.
 */
enum WorkItemStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Scheduled = 'scheduled';
    case Postponed = 'postponed';
    case Archived = 'archived';

    /**
     * Statuses that still represent open work.
     *
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [
            self::Pending->value,
            self::Open->value,
            self::InProgress->value,
            self::OnHold->value,
            self::Waiting->value,
            self::Scheduled->value,
            self::Postponed->value,
        ];
    }

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

    public function isOpen(): bool
    {
        return in_array($this->value, self::openValues(), true);
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Waiting => 'Waiting',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Scheduled => 'Scheduled',
            self::Postponed => 'Postponed',
            self::Archived => 'Archived',
        };
    }
}
