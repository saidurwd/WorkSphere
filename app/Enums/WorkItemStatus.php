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
 *
 * {@see TASK_CASES} narrows the union to the statuses a TASK may actually take.
 * That subset, not `cases()`, is what task validation, dropdowns and counts read.
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
     * The statuses a TASK may be set to.
     *
     * The enum is the union of every work vocabulary — To-Dos add `inbox`,
     * `planned` and `waiting`, action items add `open`, meetings add `scheduled` —
     * so most of its cases are not valid for a task. This is the subset, and it is
     * the single place that subset is written down.
     *
     * It exists because the list was previously repeated, literally, in about
     * twenty places — the `in:` validation rules, four `<select>` dropdowns, the
     * dashboard's status chart, the factory, and the meeting action-item mirror.
     * The copies had already drifted from each other and from the database column:
     * `on_hold` and `cancelled` were added to `tasks.status` by a migration and to
     * this enum, but never to the validation rules or the dropdowns, so a user
     * could not select either one. Validation and the dropdowns now both read this.
     *
     * `postponed` and `cancelled` are the last two. `postponed` is OPEN — the work
     * still exists and is still counted in the active scope — while `cancelled` is
     * closed, matching {@see openValues()}.
     */
    public const TASK_CASES = [
        self::Pending,
        self::InProgress,
        self::OnHold,
        self::Postponed,
        self::Completed,
        self::Cancelled,
    ];

    /**
     * The backing values a task may be set to, for an `in:` rule or a `whereIn`.
     *
     * @return list<string>
     */
    public static function taskValues(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::TASK_CASES);
    }

    /**
     * `value => label` for a task status dropdown or filter.
     *
     * @return array<string, string>
     */
    public static function taskOptions(): array
    {
        $options = [];

        foreach (self::TASK_CASES as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Whether this status may be set on a task.
     */
    public function isTaskStatus(): bool
    {
        return in_array($this, self::TASK_CASES, true);
    }

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
