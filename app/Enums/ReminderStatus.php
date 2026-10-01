<?php

namespace App\Enums;

/**
 * Reminder lifecycle (DATABASE-ARCHITECTURE.md §4.8).
 *
 * The dispatcher selects on `pending`, so that value must stay exactly
 * `pending` — the stored rows depend on it.
 */
enum ReminderStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

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

    public function isTerminal(): bool
    {
        return $this !== self::Pending;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }
}
