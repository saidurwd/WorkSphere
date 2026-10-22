<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <x-filament::button size="sm" color="gray" wire:click="shift(-1)">Previous</x-filament::button>
            <x-filament::button size="sm" color="gray" wire:click="today">Today</x-filament::button>
            <x-filament::button size="sm" color="gray" wire:click="shift(1)">Next</x-filament::button>
            <span class="ml-2 text-lg font-semibold">{{ $anchor->format('F Y') }}</span>
        </div>

        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg bg-gray-200 text-sm dark:bg-white/10">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $name)
                <div class="bg-gray-50 p-2 text-center font-medium text-gray-600 dark:bg-gray-900 dark:text-gray-400">{{ $name }}</div>
            @endforeach

            @foreach ($days as $day)
                @php($key = $day->toDateString())
                <div @class(['min-h-24 bg-white p-2 dark:bg-gray-950', 'opacity-50' => $day->month !== $anchor->month, 'ring-2 ring-primary-500 ring-inset' => $key === $today])>
                    <div class="mb-1 text-xs font-semibold">{{ $day->format('j') }}</div>

                    @foreach ($meetings->get($key, collect()) as $meeting)
                        <a href="{{ \Modules\Meetings\Filament\Resources\Meetings\MeetingResource::getUrl('view', ['record' => $meeting]) }}"
                           @class(['mb-1 block truncate rounded px-1.5 py-0.5 text-xs',
                                'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => $meeting->priority === 'urgent',
                                'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => $meeting->priority === 'important',
                                'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => ! in_array($meeting->priority, ['urgent', 'important'])])
                           title="{{ $meeting->title }}">{{ $meeting->start_time?->format('H:i') }} {{ $meeting->title }}</a>
                    @endforeach

                    @foreach ($actions->get($key, collect()) as $action)
                        <a href="{{ \Modules\Meetings\Filament\Resources\Meetings\MeetingResource::getUrl('view', ['record' => $action->meeting_id]) }}"
                           class="mb-1 block truncate rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-white/10 dark:text-gray-300"
                           title="Action due: {{ $action->title }}">Action: {{ $action->title }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
