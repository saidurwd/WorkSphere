<?php

namespace Modules\Meetings\Filament\Resources\Meetings\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;

/**
 * Who is invited and how they responded. Changing the list needs `update` on the
 * meeting; the person cannot be swapped on an existing row, only removed.
 */
class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Participants';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->label('Person')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->required()->disabledOn('edit'),
            Select::make('participant_type')->options(MeetingResource::PARTICIPANT_TYPES)->default('member')->required(),
            Select::make('attendance_status')->options(MeetingResource::ATTENDANCE)->default('invited')->required(),
            Textarea::make('remarks')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name,email'))
            ->columns([
                TextColumn::make('user.name')->label('Name'),
                TextColumn::make('participant_type')->badge()->label('Role'),
                TextColumn::make('attendance_status')->badge()->label('Attendance'),
                TextColumn::make('remarks')->limit(50)->placeholder('-')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add participant')->mutateDataUsing(function (array $data): array {
                    abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403);

                    return $data + ['invited_at' => now()];
                }),
            ])
            ->recordActions([
                EditAction::make()->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403)),
                DeleteAction::make()->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403)),
            ]);
    }
}
