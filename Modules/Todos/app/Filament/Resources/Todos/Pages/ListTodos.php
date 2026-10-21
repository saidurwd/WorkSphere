<?php

namespace Modules\Todos\Filament\Resources\Todos\Pages;

use App\Enums\WorkItemStatus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Todos\Filament\Resources\Todos\TodoResource;

class ListTodos extends ListRecords
{
    protected static string $resource = TodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * §8.1 — the Inbox is `status = inbox` over the same list and filters, so the
     * shortcut and the list cannot drift apart.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'inbox' => Tab::make('Inbox')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->status(WorkItemStatus::Inbox)),
        ];
    }
}
