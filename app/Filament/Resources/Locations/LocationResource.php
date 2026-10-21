<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\ReferenceDataResource;
use App\Models\Location;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends ReferenceDataResource
{
    protected static ?string $model = Location::class;

    protected static ?string $recordTitleAttribute = 'location_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('location_code')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('location_name')->required()->maxLength(255),
            Textarea::make('address')->maxLength(500)->columnSpanFull(),
            TextInput::make('city')->maxLength(120),
            TextInput::make('country')->maxLength(120),
            static::statusSelect(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location_code')->searchable()->sortable(),
                TextColumn::make('location_name')->searchable()->sortable(),
                TextColumn::make('city')->searchable(),
                TextColumn::make('country')->toggleable(),
                static::statusColumn(),
            ])
            ->defaultSort('location_name')
            ->filters([
                static::statusFilter(),
            ])
            ->recordActions(static::recordActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
