<?php

namespace App\Enums;

/**
 * To-Do link kinds (DATABASE-ARCHITECTURE.md §4.4).
 *
 * `blocks` and `blocked_by` are inverses of each other rather than two unrelated
 * labels, so the reverse resolution in TodoLinkService can rely on the pair.
 */
enum LinkType: string
{
    case Related = 'related';
    case RelatesTo = 'relates_to';
    case Blocks = 'blocks';
    case BlockedBy = 'blocked_by';
    case DerivedFrom = 'derived_from';

    /**
     * The inverse of this link type, or null when the link is symmetric.
     */
    public function inverse(): ?self
    {
        return match ($this) {
            self::Blocks => self::BlockedBy,
            self::BlockedBy => self::Blocks,
            self::Related, self::RelatesTo, self::DerivedFrom => null,
        };
    }

    public function isSymmetric(): bool
    {
        return $this->inverse() === null;
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

    public function label(): string
    {
        return match ($this) {
            self::Related => 'Related',
            self::RelatesTo => 'Relates to',
            self::Blocks => 'Blocks',
            self::BlockedBy => 'Blocked by',
            self::DerivedFrom => 'Derived from',
        };
    }
}
