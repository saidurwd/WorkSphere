<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('permissions.permission_name')
                    ->badge()
                    ->columnSpanFull(),
                TextEntry::make('users.name')
                    ->label('Users')
                    ->badge()
                    ->columnSpanFull(),
            ]);
    }
}
