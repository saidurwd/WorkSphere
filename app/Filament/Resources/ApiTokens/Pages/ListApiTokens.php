<?php

namespace App\Filament\Resources\ApiTokens\Pages;

use App\Filament\Resources\ApiTokens\ApiTokenResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;

class ListApiTokens extends ListRecords
{
    protected static string $resource = ApiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revokeOthers')
                ->label('Revoke all others')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Revoke every token on your account except the one making this request?')
                ->action(function (): void {
                    abort_unless(Gate::allows('system.tokens'), 403);

                    $user = auth()->user();
                    $current = $user->currentAccessToken()?->getKey();

                    // Excluding the current token keeps the operator's own session
                    // alive when it is token-authenticated.
                    $deleted = PersonalAccessToken::query()
                        ->where('tokenable_type', $user::class)
                        ->where('tokenable_id', $user->getKey())
                        ->when($current !== null, fn ($query) => $query->whereKeyNot($current))
                        ->delete();

                    Notification::make()
                        ->title("{$deleted} ".($deleted === 1 ? 'token' : 'tokens').' revoked')
                        ->success()
                        ->send();
                }),
        ];
    }
}
