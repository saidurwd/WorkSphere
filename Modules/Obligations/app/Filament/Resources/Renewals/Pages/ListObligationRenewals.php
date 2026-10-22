<?php

namespace Modules\Obligations\Filament\Resources\Renewals\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Obligations\Filament\Resources\Renewals\ObligationRenewalResource;

class ListObligationRenewals extends ListRecords
{
    protected static string $resource = ObligationRenewalResource::class;
}
