<?php

namespace Modules\Obligations\Filament\Resources\Obligations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;
use Modules\Obligations\Services\ObligationActivitySynchroniser;

class EditObligation extends EditRecord
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $old = $record->toArray();

        $record->update($data);

        app(ObligationActivitySynchroniser::class)->record(
            $record,
            'UPDATED',
            $old,
            $record->toArray(),
            'Obligation updated',
            auth()->id(),
            request()->ip(),
            request()->userAgent(),
        );

        return $record;
    }
}
