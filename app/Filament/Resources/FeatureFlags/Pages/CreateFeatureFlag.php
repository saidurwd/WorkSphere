<?php

namespace App\Filament\Resources\FeatureFlags\Pages;

use App\Filament\Resources\FeatureFlags\FeatureFlagResource;
use App\Services\FeatureFlags;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        // A new flag is off until somebody switches it on.
        return app(FeatureFlags::class)->put(FeatureFlagResource::payload($data, false), auth()->id());
    }
}
