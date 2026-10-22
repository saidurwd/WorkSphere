<?php

namespace Modules\Meetings\Filament\Resources\Meetings\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/**
 * The meeting's agenda. Reading follows the meeting's own visibility; changing it
 * needs `update` on the meeting.
 */
class AgendasRelationManager extends RelationManager
{
    protected static string $relationship = 'agendas';

    protected static ?string $title = 'Agenda';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('agenda_no')->label('No.')->numeric()->integer()->minValue(1)->required()
                ->default(fn (): int => ((int) $this->getOwnerRecord()->agendas()->max('agenda_no')) + 1),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->columnSpanFull(),
            Select::make('presented_by')->label('Presented by')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            TextInput::make('estimated_minutes')->numeric()->integer()->minValue(1),
            Select::make('status')
                ->options(['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed', 'skipped' => 'Skipped'])
                ->default('pending')->required(),
            TextInput::make('sort_order')->numeric()->integer()->minValue(0)->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('presentedBy:id,name'))
            ->columns([
                TextColumn::make('agenda_no')->label('No.'),
                TextColumn::make('title'),
                TextColumn::make('presentedBy.name')->label('Presenter')->placeholder('-'),
                TextColumn::make('estimated_minutes')->suffix(' min')->placeholder('-'),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->label('Add agenda item')->mutateDataUsing(function (array $data): array {
                    abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403);

                    return $data + ['sort_order' => 0];
                }),
            ])
            ->recordActions([
                EditAction::make()->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403)),
                DeleteAction::make()->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403)),
            ]);
    }
}
