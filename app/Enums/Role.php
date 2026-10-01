<?php

namespace App\Enums;

/**
 * Application role slug vocabulary.
 *
 * These are the slug values stored in `roles.slug` and referenced by
 * `config/authorization.php`. Distinct from {@see \App\Models\Role}, which is the
 * Eloquent model for that table — alias on import where both are needed.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case User = 'user';
    case Employee = 'employee';
    case Viewer = 'viewer';

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
     * Roles that are allowed to bypass object-level ownership checks.
     *
     * @return list<string>
     */
    public static function elevatedValues(): array
    {
        return [self::SuperAdmin->value, self::Admin->value];
    }

    public function isElevated(): bool
    {
        return in_array($this->value, self::elevatedValues(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::User => 'User',
            self::Employee => 'Employee',
            self::Viewer => 'Viewer',
        };
    }
}
