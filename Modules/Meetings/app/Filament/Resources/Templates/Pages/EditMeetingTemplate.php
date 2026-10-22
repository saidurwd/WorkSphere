<?php

namespace Modules\Meetings\Filament\Resources\Templates\Pages;

use App\Services\ActivityLogger;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Meetings\Filament\Resources\Templates\MeetingTemplateResource;
use Modules\Meetings\Models\MeetingTemplate;

class EditMeetingTemplate extends EditRecord
{
    protected static string $resource = MeetingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data + ['updated_by' => auth()->id()];
    }

    protected function afterSave(): void
    {
        /** @var MeetingTemplate $template */
        $template = $this->record;

        app(ActivityLogger::class)->record(MeetingTemplate::class, $template, 'updated', null, ['name' => $template->name], auth()->id());
    }
}
