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
                <x-filament::button tag="a" :href="$exportUrl" color="gray">Export CSV</x-filament::button>
                <x-filament::button tag="a" :href="$distributionExportUrl" color="gray">Export distribution</x-filament::button>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Showing {{ $rangeFrom }} to {{ $rangeTo }}.</p>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Completion by person">
                @if ($byOwner->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No tasks in this period. Widen the date range to see completion figures.</p>
                @else
                    <div class="table-responsive overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Assignee</th><th scope="col">Created</th><th scope="col">Completed</th><th scope="col">Overdue</th><th scope="col">Rate</th></tr></thead>
                            <tbody>
                                @foreach ($byOwner as $row)
                                    @php($created = (int) $row->created_total)
                                    <tr>
                                        <td class="py-1">{{ $row->assignee }}</td>
                                        <td>{{ $created }}</td>
                                        <td>{{ (int) $row->completed_total }}</td>
                                        <td>{{ (int) $row->overdue_total }}</td>
                                        <td>{{ $created === 0 ? 0 : (int) round(((int) $row->completed_total / $created) * 100) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Completion by project">
                @if ($byProject->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No project tasks in this period.</p>
                @else
                    <div class="table-responsive overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Project</th><th scope="col">Total</th><th scope="col">Completed</th></tr></thead>
                            <tbody>
                                @foreach ($byProject as $row)
                                    <tr><td class="py-1">{{ $row->project }}</td><td>{{ (int) $row->total }}</td><td>{{ (int) $row->completed }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <x-filament::section heading="Status and priority">
            @if ($distribution->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No tasks yet.</p>
            @else
                <dl class="grid grid-cols-1 gap-x-6 gap-y-1 text-sm md:grid-cols-2">
                    @foreach ($distribution as $row)
                        <dt class="text-gray-500 dark:text-gray-400">{{ \App\Support\StatusBadge::label($row->status) }} · {{ \App\Support\StatusBadge::label($row->priority) }}</dt>
                        <dd class="font-medium">{{ $row->total }}</dd>
                    @endforeach
                </dl>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
