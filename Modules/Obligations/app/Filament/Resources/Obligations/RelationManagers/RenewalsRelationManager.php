<?php

namespace Modules\Obligations\Filament\Resources\Obligations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The renewal history, read-only. Renewing is the "Renew" action on the page, which
 * writes the renewal, moves the dates and records the activity in one transaction.
 */
class RenewalsRelationManager extends RelationManager
{
    protected static string $relationship = 'renewals';

    protected static ?string $title = 'Renewals';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['vendor:id,vendor_name', 'renewedBy:id,name']))
            ->columns([
                TextColumn::make('renewal_date')->date()->sortable(),
                TextColumn::make('previous_expiry_date')->date()->label('Previous expiry'),
                TextColumn::make('new_start_date')->date(),
                TextColumn::make('new_expiry_date')->date(),
                TextColumn::make('vendor.vendor_name')->label('Vendor')->placeholder('-'),
                TextColumn::make('cost')->placeholder('-'),
                TextColumn::make('renewedBy.name')->label('By')->placeholder('-'),
            ])
            ->defaultSort('renewal_date', 'desc');
    }
}
