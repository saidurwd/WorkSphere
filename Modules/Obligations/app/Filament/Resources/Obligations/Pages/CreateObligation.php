<?php

namespace Modules\Obligations\Filament\Resources\Obligations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Services\ObligationActivitySynchroniser;

class CreateObligation extends CreateRecord
{
    protected static string $resource = ObligationResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['obligation_no'] = 'OBS-'.strtoupper(Str::random(8));
        $data['created_by'] = auth()->id();

        $obligation = Obligation::create($data);

        app(ObligationActivitySynchroniser::class)->record(
            $obligation,
            'CREATED',
            null,
            $obligation->toArray(),
            'Obligation created',
            auth()->id(),
            request()->ip(),
            request()->userAgent(),
        );

        return $obligation;
    }
}
