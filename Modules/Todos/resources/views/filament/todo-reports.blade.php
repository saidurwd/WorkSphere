<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-wrap items-end gap-4">
                <label class="text-sm">
                    <span class="mb-1 block font-medium">From</span>
                    <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
                </label>
                <label class="text-sm">
                    <span class="mb-1 block font-medium">To</span>
                    <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
                </label>
                <x-filament::button color="gray" wire:click="exportCompletion">Download CSV</x-filament::button>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach (['Open' => $summary->open, 'Overdue' => $summary->overdue, 'Completed' => $summary->completed, 'Unassigned' => $summary->unassigned] as $label => $value)
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-semibold">{{ number_format((int) $value) }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($sections as $title => $rows)
                <x-filament::section :heading="$title">
                    @forelse ($rows as $row)
                        <div class="mb-3">
                            <div class="mb-1 flex justify-between text-sm">
                                <span>{{ $row['label'] }}</span>
                                <span class="font-medium">{{ $row['value'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded bg-gray-200 dark:bg-white/10">
                                <div class="h-2 rounded bg-primary-500" style="width: {{ $row['pct'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No data for this period.</p>
                    @endforelse
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
