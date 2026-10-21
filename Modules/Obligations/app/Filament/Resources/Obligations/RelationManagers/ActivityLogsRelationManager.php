<?php

namespace Modules\Obligations\Filament\Resources\Obligations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The obligation's audit trail, read-only.
 */
class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = 'Activity';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name'))
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('action')->badge(),
                TextColumn::make('user.name')->label('By')->placeholder('System'),
                TextColumn::make('remarks')->limit(80)->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
