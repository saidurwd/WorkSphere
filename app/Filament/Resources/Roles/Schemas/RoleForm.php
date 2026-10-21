<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                // Not a column on `roles`: the grants are written by the page through
                // Role::syncPermissions(), never through mass assignment.
                CheckboxList::make('permissions')
                    ->options(fn (): array => Permission::query()->orderBy('permission_name')->pluck('permission_name', 'id')->all())
                    ->columns(3)
                    ->searchable()
                    ->bulkToggleable()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }
}
