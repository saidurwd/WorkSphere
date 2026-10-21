<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Policies\UserPolicy;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Base for the employee, company, department and location screens.
 *
 * SECURITY: reference data is the highest-privilege surface in the application,
 * so every ability is decided by {@see UserPolicy} (`user.manage`),
 * exactly as the Blade screens did — never by a role slug.
 *
 * Removal is a status change. Reference data is referenced from everywhere, so a
 * hard delete would either fail on a foreign key or cascade into unrelated
 * records; "deactivate" is reversible and invisible to consumers that filter on
 * `status`.
 */
abstract class ReferenceDataResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Reference data';

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', User::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', User::class);
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('updateAny', User::class);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function statusSelect(): Select
    {
        return Select::make('status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->default('active')
            ->required();
    }

    protected static function statusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray');
    }

    protected static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive']);
    }

    /**
     * @return list<Action>
     */
    protected static function recordActions(): array
    {
        return [
            EditAction::make(),
            Action::make('deactivate')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Model $record): bool => $record->status === 'active' && Gate::allows('deleteAny', User::class))
                ->action(function (Model $record): void {
                    abort_unless(Gate::allows('deleteAny', User::class), 403);

                    $record->update(['status' => 'inactive']);

                    Notification::make()->title('Deactivated')->success()->send();
                }),
        ];
    }
}
