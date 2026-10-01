<?php

namespace App\Enums;

/**
 * Work item status vocabulary.
 *
 * Existing vocabularies are preserved verbatim: `pending|in_progress|completed`
 * on `tasks`, `open|in_progress|on_hold|completed|cancelled` on
 * `meeting_action_items` and `scheduled|in_progress|completed|cancelled|postponed`
 * on `meetings`. Nothing is renamed, so no stored value changes meaning.
 *
 * The `Inbox` and `Planned` cases belong to the To-Do vocabulary
 * (TODO-MODULE-SPECIFICATION.md §3.1) and are additive for the existing tables.
 */
enum WorkItemStatus: string
{
    case Inbox = 'inbox';
    case Planned = 'planned';
    case Pending = 'pending';
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Scheduled = 'scheduled';
    case Postponed = 'postponed';
    case Skipped = 'skipped';
    case Archived = 'archived';

    /**
     * Statuses that still represent open work.
     *
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [
            self::Inbox->value,
            self::Planned->value,
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
     * Statuses that require a `waiting_on` value before they may be set
     * (TODO-MODULE-SPECIFICATION.md §3.2).
     *
     * @return list<string>
     */
    public static function waitingValues(): array
    {
        return [self::Waiting->value];
    }

    /**
     * Statuses that may be restored to by unarchiving.
     *
     * @return list<string>
     */
    public static function archivableValues(): array
    {
        return [
            self::Inbox->value,
            self::Planned->value,
            self::Pending->value,
            self::Open->value,
            self::InProgress->value,
            self::OnHold->value,
            self::Waiting->value,
            self::Scheduled->value,
            self::Postponed->value,
            self::Completed->value,
            self::Cancelled->value,
            self::Skipped->value,
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

    public function requiresWaitingOn(): bool
    {
        return in_array($this->value, self::waitingValues(), true);
    }

    public function isArchivable(): bool
    {
        return in_array($this->value, self::archivableValues(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Inbox => 'Inbox',
            self::Planned => 'Planned',
            self::Pending => 'Pending',
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Waiting => 'Waiting',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Scheduled => 'Scheduled',
            self::Postponed => 'Postponed',
            self::Skipped => 'Skipped',
            self::Archived => 'Archived',
        };
    }
}
