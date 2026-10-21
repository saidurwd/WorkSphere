<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-filament::button size="sm" color="gray" wire:click="shift(-1)">Previous</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="today">Today</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="shift(1)">Next</x-filament::button>
                <span class="ml-2 text-lg font-semibold">{{ $anchor->format($mode === 'week' ? '\W\e\e\k \o\f M j, Y' : 'F Y') }}</span>
            </div>

            <div class="flex gap-2">
                <x-filament::button size="sm" :color="$mode === 'month' ? 'primary' : 'gray'" wire:click="setMode('month')">Month</x-filament::button>
                <x-filament::button size="sm" :color="$mode === 'week' ? 'primary' : 'gray'" wire:click="setMode('week')">Week</x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg bg-gray-200 text-sm dark:bg-white/10">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $name)
                <div class="bg-gray-50 p-2 text-center font-medium text-gray-600 dark:bg-gray-900 dark:text-gray-400">{{ $name }}</div>
            @endforeach

            @foreach ($days as $day)
                @php($key = $day->toDateString())
                <div @class([
                    'min-h-24 bg-white p-2 dark:bg-gray-950',
                    'opacity-50' => ! $inMonth($day),
                    'ring-2 ring-primary-500 ring-inset' => $key === $today,
                ])>
                    <div class="mb-1 text-xs font-semibold">{{ $day->format('j') }}</div>

                    @foreach ($byDate->get($key, collect()) as $todo)
                        <a href="{{ \Modules\Todos\Filament\Resources\Todos\TodoResource::getUrl('view', ['record' => $todo]) }}"
                           class="mb-1 block truncate rounded bg-primary-50 px-1.5 py-0.5 text-xs text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300"
                           title="{{ $todo->title }}{{ $todo->assignee ? ' — '.$todo->assignee->name : '' }}">
                            {{ $todo->title }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
