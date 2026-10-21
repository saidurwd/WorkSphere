<x-filament-panels::page>
    <x-filament::section heading="Scheduled events" :description="'Evaluated in '.$timezone">
        @if ($events === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">Nothing is scheduled, so reminders and notifications will never fire.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-1">Command</th>
                            <th class="py-1">Cron</th>
                            <th class="py-1">Next run</th>
                            <th class="py-1">Timezone</th>
                            <th class="py-1">Protections</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td class="py-1">
                                    <div class="font-mono">{{ $event['command'] ?: $event['description'] }}</div>
                                    @if ($event['command'] && $event['description'] && $event['description'] !== 'Closure')
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $event['description'] }}</div>
                                    @endif
                                </td>
                                <td class="py-1 font-mono">{{ $event['expression'] }}</td>
                                <td class="py-1">{{ $event['next_run'] ?? '-' }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['next_run_human'] }}</span></td>
                                <td class="py-1">{{ $event['timezone'] }}</td>
                                <td class="py-1">
                                    @if ($event['missing'] === [])
                                        <x-filament::badge color="success">Guarded</x-filament::badge>
                                    @else
                                        @foreach ($event['missing'] as $missing)
                                            <x-filament::badge color="warning">No {{ $missing }}</x-filament::badge>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
