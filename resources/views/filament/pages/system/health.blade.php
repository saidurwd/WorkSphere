<x-filament-panels::page>
    <div wire:poll.30s class="space-y-6">
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-base font-semibold">
                        {{ $ok ? 'All checks passing' : 'One or more checks failing' }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $report['application']['environment'] }} · {{ $report['application']['time'] }}
                    </div>
                </div>

                <div class="flex gap-2">
                    <x-filament::button tag="a" href="{{ route('livez') }}" target="_blank" color="gray" size="sm">
                        Live probe
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ route('readyz') }}" target="_blank" color="gray" size="sm">
                        Ready probe
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($checks as $check)
                <x-filament::section :heading="$check['label']">
                    <x-slot name="afterHeader">
                        <x-filament::badge :color="$check['color']">{{ ucfirst($check['status']) }}</x-filament::badge>
                    </x-slot>

                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ $check['explanation'] }}</p>

                    <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                        @foreach ($check['facts'] as $label => $value)
                            <dt class="capitalize text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="break-words font-mono text-xs">{{ $value }}</dd>
                        @endforeach
                    </dl>
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
