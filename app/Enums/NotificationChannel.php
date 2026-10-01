<?php

namespace App\Enums;

/**
 * Delivery channel for a notification.
 *
 * `database` and `mail` are the two channels the application can actually
 * deliver today. `sms` and `in_app` are declared but not wired; see
 * TODO-MODULE-SPECIFICATION.md §6.
 */
enum NotificationChannel: string
{
    case Database = 'database';
    case Mail = 'mail';
    case Sms = 'sms';
    case InApp = 'in_app';

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
     * Channels the runtime can deliver right now.
     *
     * @return list<string>
     */
    public static function deliverableValues(): array
    {
        return [self::Database->value, self::Mail->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Database => 'In-App',
            self::Mail => 'Email',
            self::Sms => 'SMS',
            self::InApp => 'In-App',
        };
    }
}
