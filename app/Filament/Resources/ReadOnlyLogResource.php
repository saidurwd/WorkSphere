<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for the administration log viewers.
 *
 * The logs are append-only evidence: nothing here can create, edit or delete a
 * row. Access matches the `admin` middleware that guarded the Blade screens — a
 * role named in `authorization.admin_roles`.
 */
abstract class ReadOnlyLogResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && array_intersect(config('authorization.admin_roles', ['admin', 'super-admin']), $user->roleSlugs()) !== [];
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * A created-between filter over the given timestamp column.
     */
    protected static function dateRangeFilter(string $column): Filter
    {
        return Filter::make('date_range')
            ->schema([
                DatePicker::make('from'),
                DatePicker::make('until'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate($column, '<=', $date)));
    }
}
