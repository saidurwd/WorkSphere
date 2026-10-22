<?php

namespace Modules\Meetings\Filament\Resources\ActionItems;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Meetings\Filament\Resources\ActionItems\Pages\ListMeetingActionItems;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Models\MeetingActionItem;

/**
 * Every action item across the meetings the viewer can see — the register and, with
 * its filters, the overdue report. Items are edited from their meeting.
 */
class MeetingActionItemResource extends Resource
{
    protected static ?string $model = MeetingActionItem::class;

    protected static ?string $modelLabel = 'action item';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'Action items';

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'meeting-action-items';

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
            ->with(['meeting:id,meeting_no,title', 'assignedTo:id,name', 'assignedDepartment:id,department_name', 'task:id,task_no,title'])
            ->visibleTo(auth()->user());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('meeting.title')->label('Meeting')->limit(30)->searchable(),
                TextColumn::make('assignedTo.name')->label('Assigned to')->placeholder('-'),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_date')->date()->sortable()->placeholder('-'),
                TextColumn::make('assignedDepartment.department_name')->label('Department')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(['open' => 'Open', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
                SelectFilter::make('priority')->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']),
                SelectFilter::make('assigned_to')->label('Assigned to')->relationship('assignedTo', 'name')->searchable()->preload(),
                SelectFilter::make('assigned_department_id')->label('Department')->relationship('assignedDepartment', 'department_name'),
                Filter::make('overdue')->toggle()->query(fn (Builder $query): Builder => $query->overdue()),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Meeting')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MeetingActionItem $record): string => MeetingResource::getUrl('view', ['record' => $record->meeting_id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingActionItems::route('/'),
        ];
    }
}
