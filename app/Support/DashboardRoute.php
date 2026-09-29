<?php

namespace App\Support;

/**
 * Single source of truth for the admin route names used across the views.
 *
 * The resource, user, role, privilege and profile routes are still served by
 * the Tyro Dashboard package while the AdminLTE migration is in progress, so
 * the names are built from the package prefix. Once those screens are rebuilt
 * natively only this class needs to change.
 */
class DashboardRoute
{
    public static function prefix(): string
    {
        return config('tyro-dashboard.routes.name_prefix', 'tyro-dashboard.');
    }

    public static function name(string $name = ''): string
    {
        return $name === '' ? rtrim(static::prefix(), '.') : rtrim(static::prefix(), '.').'.'.$name;
    }

    public static function pattern(string $pattern = '*'): string
    {
        return static::name($pattern);
    }

    /**
     * Sign-out is still served by the Tyro Login package.
     */
    public static function logout(): string
    {
        return route('tyro-login.logout');
    }
}
