<?php

namespace App\Filament\Resources\FeatureFlags\Pages;

use App\Filament\Resources\FeatureFlags\FeatureFlagResource;
use App\Services\FeatureFlags;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFeatureFlag extends EditRecord
{
    protected static string $resource = FeatureFlagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(FeatureFlags::class)->put(
            FeatureFlagResource::payload([...$data, 'key' => $record->key], (bool) $record->is_enabled),
            auth()->id(),
        );
    }
}
