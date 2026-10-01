<?php

namespace App\Enums;

/**
 * Work item priority vocabulary.
 *
 * Two disjoint scales exist in the schema today: `low|medium|high` on `tasks`
 * and `normal|important|urgent` on `meetings`. Both are preserved as cases so
 * no stored value has to be rewritten.
 */
enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';

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

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Normal => 'Normal',
            self::Important => 'Important',
            self::Urgent => 'Urgent',
        };
    }
}
