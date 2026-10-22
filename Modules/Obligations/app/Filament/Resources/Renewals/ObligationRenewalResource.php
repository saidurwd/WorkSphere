<?php

namespace Modules\Obligations\Filament\Resources\Renewals;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;
use Modules\Obligations\Filament\Resources\Renewals\Pages\ListObligationRenewals;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationRenewal;
use UnitEnum;

/**
 * The renewal history across every obligation the viewer can see, read-only.
 * Renewing is the "Renew" action on the obligation.
 */
class ObligationRenewalResource extends Resource
{
    protected static ?string $model = ObligationRenewal::class;

    protected static ?string $modelLabel = 'obligation renewal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Obligations';

    protected static ?string $navigationLabel = 'Obligation renewals';

    protected static ?int $navigationSort = 36;

    protected static ?string $slug = 'obligation-renewals';

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
            ->with(['obligation:id,obligation_no,title', 'renewedBy:id,name', 'vendor:id,vendor_name'])
            ->whereHas('obligation', fn (Builder $o): Builder => $o->visibleTo(auth()->user()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('renewal_date')->date()->sortable(),
                TextColumn::make('obligation.obligation_no')->label('Obligation')->searchable(),
                TextColumn::make('obligation.title')->label('Title')->limit(40)->searchable(),
                TextColumn::make('previous_expiry_date')->date()->label('Previous expiry'),
                TextColumn::make('new_expiry_date')->date()->label('New expiry'),
                TextColumn::make('vendor.vendor_name')->label('Vendor')->placeholder('-')->toggleable(),
                TextColumn::make('cost')->placeholder('-')->toggleable(),
                TextColumn::make('renewedBy.name')->label('By')->placeholder('-')->toggleable(),
            ])
            ->defaultSort('renewal_date', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Obligation')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ObligationRenewal $record): string => ObligationResource::getUrl('view', ['record' => $record->obligation_id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObligationRenewals::route('/'),
        ];
    }
}
