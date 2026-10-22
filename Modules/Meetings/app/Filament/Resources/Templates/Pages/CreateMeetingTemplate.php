<?php

namespace Modules\Meetings\Filament\Resources\Templates\Pages;

use App\Services\ActivityLogger;
use Filament\Resources\Pages\CreateRecord;
use Modules\Meetings\Filament\Resources\Templates\MeetingTemplateResource;
use Modules\Meetings\Models\MeetingTemplate;

class CreateMeetingTemplate extends CreateRecord
{
    protected static string $resource = MeetingTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['created_by' => auth()->id()];
    }

    protected function afterCreate(): void
    {
        /** @var MeetingTemplate $template */
        $template = $this->record;

        app(ActivityLogger::class)->record(MeetingTemplate::class, $template, 'created', null, ['name' => $template->name], auth()->id());
    }
}
