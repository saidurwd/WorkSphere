<?php

namespace App\Filament\Pages\System;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

/**
 * Base for the system administration screens.
 *
 * ONE PERMISSION PER SCREEN rather than a single `system.manage` for all of them:
 * an operator who can read the health of the system is not thereby handed the
 * ability to change how it behaves. Each page names its Gate ability, and
 * `system.manage` remains the super-admin shortcut inside the Gate itself.
 */
abstract class SystemPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'System';

    /**
     * The Gate ability that guards the screen, e.g. `system.settings`.
     */
    abstract protected static function ability(): string;

    public static function canAccess(): bool
    {
        return Gate::allows(static::ability());
    }
}
