<?php

namespace Modules\Obligations\Filament\Resources\Documents\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Obligations\Filament\Resources\Documents\ObligationDocumentResource;

class ListObligationDocuments extends ListRecords
{
    protected static string $resource = ObligationDocumentResource::class;
}
