<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ($stats as $label => $value)
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-semibold">{{ number_format($value) }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Upcoming meetings">
                @forelse ($upcoming as $meeting)
                    <a href="{{ \Modules\Meetings\Filament\Resources\Meetings\MeetingResource::getUrl('view', ['record' => $meeting]) }}" class="mb-2 block text-sm hover:underline">
                        <div class="flex justify-between gap-2"><span class="truncate">{{ $meeting->title }}</span><span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $meeting->meeting_date->format('M d') }} {{ $meeting->start_time?->format('H:i') }}</span></div>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing scheduled.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section heading="My pending actions">
                @forelse ($mine as $action)
                    <div class="mb-2 text-sm">
                        <div class="flex justify-between gap-2"><span class="truncate">{{ $action->title }}</span><span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $action->due_date?->format('M d') ?? 'No date' }}</span></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $action->meeting?->title }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing waiting on you.</p>
                @endforelse
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
