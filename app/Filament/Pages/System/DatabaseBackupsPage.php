<?php

namespace App\Filament\Pages\System;

use App\Services\DatabaseBackups;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Take, download and delete encrypted database backups. Super-admin only; the
 * work itself is in {@see DatabaseBackups}.
 */
class DatabaseBackupsPage extends SystemPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Database backups';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Database backups';

    protected static ?string $slug = 'system/database-backups';

    protected string $view = 'filament.pages.system.database-backups';

    protected static function ability(): string
    {
        return 'super-admin-only';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'backups' => app(DatabaseBackups::class)->list(),
            'retention' => DatabaseBackups::RETENTION,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label('Create backup')
                ->icon('heroicon-o-plus')
                ->schema([
                    TextInput::make('name')->label('Name (optional)')->maxLength(60),
                ])
                ->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);

                    $filename = app(DatabaseBackups::class)->create((string) ($data['name'] ?? ''), auth()->user());

                    $filename === null
                        ? Notification::make()->title('The backup could not be taken.')->danger()->send()
                        : Notification::make()->title('Backup created and encrypted')->success()->send();
                }),
        ];
    }

    public function deleteBackup(string $filename): void
    {
        abort_unless(static::canAccess(), 403);

        app(DatabaseBackups::class)->delete($filename, auth()->user());

        Notification::make()->title('Backup deleted')->success()->send();
    }
}
