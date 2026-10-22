<x-filament-panels::page>
    <x-filament::section heading="Backups" :description="'Encrypted with the application key. The newest '.$retention.' are kept.'">
        @if ($backups === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">No backups yet. Use “Create backup” to take one.</p>
        @else
            <div class="table-responsive overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">File</th><th scope="col">Size</th><th scope="col">Taken</th><th scope="col"></th></tr></thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr wire:key="backup-{{ $backup['filename'] }}">
                                <td class="py-1 font-mono">{{ $backup['filename'] }}</td>
                                <td>{{ $backup['human_size'] }}</td>
                                <td>{{ $backup['last_modified_human'] }}</td>
                                <td class="whitespace-nowrap text-right">
                                    <x-filament::link :href="route('dashboard.database-backups.download', $backup['filename'])">Download</x-filament::link>
                                    <x-filament::link tag="button" color="danger" wire:click="deleteBackup('{{ $backup['filename'] }}')" wire:confirm="Delete this backup?">Delete</x-filament::link>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
