<?php

namespace Modules\Obligations\Filament\Resources\Vendors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Obligations\Filament\Resources\Vendors\VendorResource;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;
}
