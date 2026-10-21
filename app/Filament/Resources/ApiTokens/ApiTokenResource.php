<?php

namespace App\Filament\Resources\ApiTokens;

use App\Filament\Resources\ApiTokens\Pages\ListApiTokens;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * API token review and revocation.
 *
 * Tokens are created over the API (super-admin only, audited, returned exactly
 * once). This screen does NOT create them: reading your own tokens and revoking
 * them is hygiene; minting a standing credential is an escalation and stays on the
 * audited path. Sanctum stores only a hash, so there is no "token" column — it
 * would be a lie.
 *
 * Scoped to the signed-in user's own tokens, so an administrator can neither see
 * nor revoke another account's credentials by guessing an id.
 */
class ApiTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static ?string $modelLabel = 'API token';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        return Gate::allows('system.tokens');
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
        return Gate::allows('system.tokens') && $record->tokenable_id === auth()->id();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('abilities')->badge()->placeholder('-'),
                TextColumn::make('last_used_at')->since()->placeholder('Never')->sortable(),
                TextColumn::make('expires_at')->dateTime()->placeholder('No expiry'),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_used_at', 'desc')
            ->recordActions([
                Action::make('revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (PersonalAccessToken $record): void {
                        abort_unless(static::canDelete($record), 403);

                        $record->delete();

                        Notification::make()->title('Token revoked')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiTokens::route('/'),
        ];
    }
}
