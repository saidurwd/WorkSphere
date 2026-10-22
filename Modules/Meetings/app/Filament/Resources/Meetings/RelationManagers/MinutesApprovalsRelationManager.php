<?php

namespace Modules\Meetings\Filament\Resources\Meetings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Who has to sign the minutes off, and what each decided. Read-only: decisions are
 * made with the Approve and Return buttons on the meeting.
 */
class MinutesApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'minutesApprovals';

    protected static ?string $title = 'Minutes approval';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('approver:id,name'))
            ->columns([
                TextColumn::make('step_no')->label('Step'),
                TextColumn::make('approver.name')->label('Approver'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'returned' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('comments')->wrap()->placeholder('-'),
                TextColumn::make('action_at')->dateTime()->placeholder('-'),
            ])
            ->defaultSort('step_no');
    }
}
