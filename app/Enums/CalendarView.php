<?php

namespace App\Enums;

/**
 * Calendar granularity offered on the To-Do calendar.
 */
enum CalendarView: string
{
    case Month = 'month';
    case Week = 'week';

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
     * Resolve user input, falling back to the month view rather than throwing.
     *
     * Named `parse()` rather than `from()` because `BackedEnum::from()` is
     * already declared by PHP and redeclaring it is a fatal error.
     *
     * @param  mixed  $value
     */
    public static function parse($value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::Month) : self::Month;
    }

    public function label(): string
    {
        return match ($this) {
            self::Month => 'Month',
            self::Week => 'Week',
        };
    }
}
