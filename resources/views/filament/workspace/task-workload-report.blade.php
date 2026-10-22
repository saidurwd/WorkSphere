<x-filament-panels::page>
    <x-filament::section heading="Assignees">
        <x-slot name="afterHeader">
            <x-filament::button tag="a" :href="$exportUrl" color="gray" size="sm">Export CSV</x-filament::button>
        </x-slot>

        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No assigned tasks. Nobody is carrying work yet.</p>
        @else
            <div class="table-responsive overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Assignee</th><th scope="col">Total</th><th scope="col">Completed</th><th scope="col">Overdue</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="py-1">{{ $row->assignee }}</td>
                                <td>{{ (int) $row->total }}</td>
                                <td>{{ (int) $row->completed }}</td>
                                <td>@if ((int) $row->overdue > 0)<span class="font-semibold text-danger-600">{{ (int) $row->overdue }}</span>@else 0 @endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
