<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\ReferenceDataResource;
use App\Models\Company;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyResource extends ReferenceDataResource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('company_code')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('company_name')->required()->maxLength(255),
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
                TextColumn::make('company_code')->searchable()->sortable(),
                TextColumn::make('company_name')->searchable()->sortable(),
                TextColumn::make('city')->searchable(),
                TextColumn::make('country')->toggleable(),
                static::statusColumn(),
            ])
            ->defaultSort('company_name')
            ->filters([
                static::statusFilter(),
            ])
            ->recordActions(static::recordActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
