<?php

namespace Modules\Meetings\Filament\Resources\Decisions;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Meetings\Filament\Resources\Decisions\Pages\ListMeetingDecisions;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Models\MeetingDecision;

/**
 * The decision register across the meetings the viewer can see. Decisions are
 * recorded and edited from their meeting.
 */
class MeetingDecisionResource extends Resource
{
    protected static ?string $model = MeetingDecision::class;

    protected static ?string $modelLabel = 'decision';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Decision register';

    protected static ?int $navigationSort = 46;

    protected static ?string $slug = 'meeting-decisions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['meeting:id,meeting_no,title', 'approvedBy:id,name'])
            ->visibleTo(auth()->user());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('decision_date')->label('Date')->date()->sortable()->placeholder('-'),
                TextColumn::make('decision_title')->label('Decision')->searchable()->limit(60),
                TextColumn::make('meeting.title')->label('Meeting')->limit(30)->searchable(),
                TextColumn::make('decision_type')->label('Type')->badge(),
                TextColumn::make('decision_status')->label('Status')->badge(),
                TextColumn::make('approvedBy.name')->label('Approved by')->placeholder('-')->toggleable(),
            ])
            ->defaultSort('decision_date', 'desc')
            ->filters([
                SelectFilter::make('decision_type')->label('Type')->options([
                    'approved' => 'Approved', 'rejected' => 'Rejected', 'deferred' => 'Deferred',
                    'noted' => 'Noted', 'further_discussion_required' => 'Further discussion required',
                ]),
                SelectFilter::make('decision_status')->label('Status')->options(['active' => 'Active', 'superseded' => 'Superseded', 'cancelled' => 'Cancelled']),
                Filter::make('date_range')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('decision_date', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('decision_date', '<=', $d))),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Meeting')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MeetingDecision $record): string => MeetingResource::getUrl('view', ['record' => $record->meeting_id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingDecisions::route('/'),
        ];
    }
}
