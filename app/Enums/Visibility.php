<?php

namespace App\Enums;

/**
 * To-Do visibility vocabulary (TODO-MODULE-SPECIFICATION.md §4.1).
 *
 * `Team` is the only value that widens visibility beyond the To-Do's own
 * parties: it makes the To-Do visible to everyone in the same department.
 */
enum Visibility: string
{
    case Personal = 'personal';
    case Team = 'team';
    case Private = 'private';

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
     * Whether this visibility widens the audience beyond the To-Do's own parties.
     */
    public function isShared(): bool
    {
        return $this !== self::Personal;
    }

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Team => 'Team',
            self::Private => 'Private',
        };
    }
}
