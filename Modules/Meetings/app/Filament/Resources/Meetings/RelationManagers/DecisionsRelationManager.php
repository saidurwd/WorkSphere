<?php

namespace Modules\Meetings\Filament\Resources\Meetings\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/**
 * Decisions taken at the meeting. Changing them needs `update` on the meeting, and
 * the author and last editor are recorded.
 */
class DecisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'decisions';

    protected static ?string $title = 'Decisions';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('decision_no')->label('No.')->numeric()->integer()->minValue(1)->required()
                ->default(fn (): int => ((int) $this->getOwnerRecord()->decisions()->max('decision_no')) + 1),
            TextInput::make('decision_title')->label('Title')->required()->maxLength(255),
            Textarea::make('decision_description')->label('Description')->columnSpanFull(),
            Select::make('decision_type')->label('Type')
                ->options([
                    'approved' => 'Approved', 'rejected' => 'Rejected', 'deferred' => 'Deferred',
                    'noted' => 'Noted', 'further_discussion_required' => 'Further discussion required',
                ])->required(),
            Select::make('decision_status')->label('Status')
                ->options(['active' => 'Active', 'superseded' => 'Superseded', 'cancelled' => 'Cancelled'])
                ->default('active')->required(),
            DatePicker::make('decision_date'),
            DatePicker::make('effective_date'),
            Select::make('approved_by')->label('Approved by')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            Textarea::make('remarks')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('decision_no')->label('No.'),
                TextColumn::make('decision_title')->label('Decision')->limit(60),
                TextColumn::make('decision_type')->label('Type')->badge(),
                TextColumn::make('decision_status')->label('Status')->badge(),
                TextColumn::make('decision_date')->date()->placeholder('-'),
            ])
            ->defaultSort('decision_no', 'desc')
            ->headerActions([
                CreateAction::make()->label('Record decision')->mutateDataUsing(function (array $data): array {
                    abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403);

                    return $data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()];
                }),
            ])
            ->recordActions([
                EditAction::make()
                    ->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403))
                    ->mutateDataUsing(fn (array $data): array => $data + ['updated_by' => auth()->id()]),
                DeleteAction::make()->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403)),
            ]);
    }
}
