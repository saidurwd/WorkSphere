<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="By person">
            <div class="table-responsive overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Person</th><th scope="col">Open</th><th scope="col">In progress</th><th scope="col">Completed</th><th scope="col">Overdue</th></tr></thead>
                    <tbody>
                        @forelse ($people as $row)
                            <tr><td class="py-1">{{ $row->assignedTo?->name ?? 'Unknown' }}</td><td>{{ (int) $row->open }}</td><td>{{ (int) $row->in_progress }}</td><td>{{ (int) $row->completed }}</td><td>{{ (int) $row->overdue }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="py-2 text-gray-500 dark:text-gray-400">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="By department">
            <div class="table-responsive overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Department</th><th scope="col">Total</th><th scope="col">Completed</th><th scope="col">Pending</th><th scope="col">Overdue</th></tr></thead>
                    <tbody>
                        @forelse ($departments as $row)
                            <tr><td class="py-1">{{ $row->assignedDepartment?->department_name ?? 'Unknown' }}</td><td>{{ (int) $row->total }}</td><td>{{ (int) $row->completed }}</td><td>{{ (int) $row->pending }}</td><td>{{ (int) $row->overdue }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="py-2 text-gray-500 dark:text-gray-400">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
