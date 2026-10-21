<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Queues" :description="'Connection: '.$connection">
            @if ($queues === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing is waiting.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1">Queue</th>
                            <th class="py-1">Pending</th>
                            <th class="py-1">Oldest (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($queues as $queue)
                            <tr>
                                <td class="py-1 font-mono">{{ $queue['queue'] }}</td>
                                <td class="py-1">{{ number_format($queue['pending']) }}</td>
                                <td class="py-1">{{ $queue['oldest_minutes'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Failed jobs">
            @if ($failed !== [])
                <x-slot name="afterHeader">
                    <div class="flex gap-2">
                        <x-filament::button size="sm" wire:click="retryAll" wire:confirm="Retry every failed job?">
                            Retry all
                        </x-filament::button>
                        <x-filament::button size="sm" color="danger" wire:click="flush" wire:confirm="Discard every failed job?">
                            Discard all
                        </x-filament::button>
                    </div>
                </x-slot>
            @endif

            @if ($failed === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">No failed jobs.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="py-1">Job</th>
                                <th class="py-1">Queue</th>
                                <th class="py-1">Attempts</th>
                                <th class="py-1">Failed at</th>
                                <th class="py-1">Error</th>
                                <th class="py-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($failed as $job)
                                <tr wire:key="failed-{{ $job['id'] }}">
                                    <td class="py-1 font-semibold">{{ $job['display_name'] }}</td>
                                    <td class="py-1">{{ $job['queue'] }}</td>
                                    <td class="py-1">{{ $job['attempts'] }}</td>
                                    <td class="py-1">{{ $job['failed_at'] ?? '-' }}</td>
                                    <td class="py-1 text-danger-600">{{ $job['error'] }}</td>
                                    <td class="py-1 whitespace-nowrap">
                                        <x-filament::link tag="button" wire:click="retry('{{ $job['id'] }}')">Retry</x-filament::link>
                                        <x-filament::link tag="button" color="danger" wire:click="forget('{{ $job['id'] }}')" wire:confirm="Discard this failed job?">Discard</x-filament::link>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        @if ($batches !== [])
            <x-filament::section heading="Recent batches">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1">Name</th>
                            <th class="py-1">Total</th>
                            <th class="py-1">Pending</th>
                            <th class="py-1">Failed</th>
                            <th class="py-1">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            <tr>
                                <td class="py-1">{{ $batch['name'] }}</td>
                                <td class="py-1">{{ $batch['total'] }}</td>
                                <td class="py-1">{{ $batch['pending'] }}</td>
                                <td class="py-1">{{ $batch['failed'] }}</td>
                                <td class="py-1">{{ $batch['progress'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
