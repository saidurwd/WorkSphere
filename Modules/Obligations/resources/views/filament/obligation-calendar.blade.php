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

                    @foreach ($expiries->get($key, collect()) as $obligation)
                        <a href="{{ \Modules\Obligations\Filament\Resources\Obligations\ObligationResource::getUrl('view', ['record' => $obligation]) }}"
                           @class(['mb-1 block truncate rounded px-1.5 py-0.5 text-xs',
                                'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => in_array($obligation->risk_level, ['critical', 'high']),
                                'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => ! in_array($obligation->risk_level, ['critical', 'high'])])
                           title="Expires: {{ $obligation->title }}">{{ $obligation->title }}</a>
                    @endforeach

                    @foreach ($tasks->get($key, collect()) as $task)
                        <a href="{{ \Modules\Tasks\Filament\Resources\Tasks\TaskResource::getUrl('view', ['record' => $task]) }}"
                           class="mb-1 block truncate rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-white/10 dark:text-gray-300"
                           title="Task due: {{ $task->title }}">Task: {{ $task->title }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
