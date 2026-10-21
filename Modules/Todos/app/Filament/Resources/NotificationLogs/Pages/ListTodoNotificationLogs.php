<?php

namespace Modules\Todos\Filament\Resources\NotificationLogs\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Todos\Filament\Resources\NotificationLogs\TodoNotificationLogResource;

class ListTodoNotificationLogs extends ListRecords
{
    protected static string $resource = TodoNotificationLogResource::class;
}
