<?php

namespace Modules\Obligations\Filament\Resources\Vendors;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Obligations\Filament\Resources\Vendors\Pages\CreateVendor;
use Modules\Obligations\Filament\Resources\Vendors\Pages\EditVendor;
use Modules\Obligations\Filament\Resources\Vendors\Pages\ListVendors;
use Modules\Obligations\Models\Vendor;

/**
 * Obligation vendors. Changing them needs `obligation.manage_vendors`; reading the
 * list needs that or `obligation.view`, because the contact details are not
 * something every signed-in user should be able to browse.
 */
class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $recordTitleAttribute = 'vendor_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 31;

    public static function canViewAny(): bool
    {
        return Gate::allows('obligation.manage_vendors') || auth()->user()?->hasPermission('obligation.view') === true;
    }

    public static function canCreate(): bool
    {
        return Gate::allows('obligation.manage_vendors');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('obligation.manage_vendors');
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('obligation.manage_vendors');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('vendor_name')->required()->maxLength(255),
            TextInput::make('contact_person')->maxLength(255),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('phone')->maxLength(255),
            TextInput::make('website')->url()->maxLength(255),
            Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active')->required(),
            Textarea::make('address')->maxLength(500)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor_name')->searchable()->sortable(),
                TextColumn::make('contact_person')->searchable()->toggleable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
            ])
            ->defaultSort('vendor_name')
            ->filters([
                SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
