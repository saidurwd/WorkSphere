<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\ReferenceDataResource;
use App\Models\Employee;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends ReferenceDataResource
{
    protected static ?string $model = Employee::class;

    protected static ?string $recordTitleAttribute = 'employee_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['department:id,department_name', 'location:id,location_name']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('employee_code')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('employee_name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('phone')->maxLength(50),
            TextInput::make('designation')->maxLength(255),
            Select::make('department_id')->relationship('department', 'department_name')->searchable()->preload(),
            Select::make('location_id')->relationship('location', 'location_name')->searchable()->preload(),
            DatePicker::make('joining_date'),
            static::statusSelect(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')->searchable()->sortable(),
                TextColumn::make('employee_name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('designation')->searchable()->toggleable(),
                TextColumn::make('department.department_name')->label('Department')->toggleable(),
                TextColumn::make('location.location_name')->label('Location')->toggleable(),
                static::statusColumn(),
            ])
            ->defaultSort('employee_name')
            ->filters([
                static::statusFilter(),
            ])
            ->recordActions(static::recordActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
