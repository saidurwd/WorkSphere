<?php

namespace App\Filament\Workspace\Pages;

use App\Services\MyWorkService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Everything assigned to me, across To-Dos, tasks, meeting action items and
 * obligations. {@see MyWorkService} decides which sources the viewer may see; the
 * counts come from those same rows, so a user without a module's permission sees
 * neither its rows nor a count that reveals how many exist.
 */
class MyWork extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'My Work';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'My Work';

    protected static ?string $slug = 'my-work';

    protected string $view = 'filament.workspace.my-work';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(MyWorkService::class);
        $data = $service->forUser(auth()->user());

        return [
            'workItems' => $data['workItems'],
            'obligations' => $data['obligations'],
            'counts' => $data['counts'],
            'urls' => $data['workItems']
                ->mapWithKeys(fn (object $row): array => [
                    (string) $row->source_type.':'.$row->source_id => $service->urlFor($row),
                ])
                ->all(),
        ];
    }
}
