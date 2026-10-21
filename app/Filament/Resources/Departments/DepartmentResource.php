<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\ReferenceDataResource;
use App\Models\Department;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends ReferenceDataResource
{
    protected static ?string $model = Department::class;

    protected static ?string $recordTitleAttribute = 'department_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('headOfDepartment:id,employee_name');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('department_code')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('department_name')->required()->maxLength(255),
            // The head is an EMPLOYEE, not a user.
            Select::make('head_of_department_id')->label('Head of department')->relationship('headOfDepartment', 'employee_name')->searchable()->preload(),
            static::statusSelect(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department_code')->searchable()->sortable(),
                TextColumn::make('department_name')->searchable()->sortable(),
                TextColumn::make('headOfDepartment.employee_name')->label('Head')->placeholder('-'),
                static::statusColumn(),
            ])
            ->defaultSort('department_name')
            ->filters([
                static::statusFilter(),
            ])
            ->recordActions(static::recordActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
