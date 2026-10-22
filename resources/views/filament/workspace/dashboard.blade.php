<x-filament-panels::page>
    @php($stats = $entries->get('personal_stats')['data'] ?? null)

    <div class="space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->format('l, F j, Y') }}</p>

        @if (is_array($stats))
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach (['tasks' => 'Tasks', 'todos' => 'To-Dos', 'meetings' => 'Meetings'] as $key => $label)
                    @if (isset($stats[$key]))
                        <x-filament::section :heading="$label">
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                                @foreach ($stats[$key] as $name => $value)
                                    <dt class="capitalize text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $name) }}</dt>
                                    <dd @class(['font-medium', 'text-danger-600' => $name === 'overdue' && $value > 0])>{{ number_format((int) $value) }}</dd>
                                @endforeach
                            </dl>
                        </x-filament::section>
                    @endif
                @endforeach
            </div>

            @if (! empty($stats['weeklyBars']))
                <x-filament::section heading="Tasks created this week">
                    <div class="flex h-32 items-end gap-3">
                        @foreach ($stats['weeklyBars'] as $bar)
                            <div class="flex flex-1 flex-col items-center gap-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $bar['value'] }}</span>
                                <div class="w-full rounded-t bg-primary-500" style="height: {{ max((int) $bar['pct'], 2) }}%"></div>
                                <span class="text-xs">{{ $bar['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @endif

        @foreach ($grouped as $group => $rows)
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($rows as $row)
                    @php($widget = $row['widget'])
                    @php($data = $row['data'])
                    @continue($widget->key() === 'personal_stats')

                    <x-filament::section :heading="$widget->label()">
                        @switch($widget->key())
                            @case('my_todos')
                            @case('my_tasks')
                                @forelse ($data as $item)
                                    <a href="{{ $item['url'] }}" class="mb-2 flex items-center justify-between gap-2 text-sm hover:underline">
                                        <span class="truncate">{{ $item['title'] }}</span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <x-filament::badge color="gray">{{ $item['status'] }}</x-filament::badge>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['due_date'] ?? '' }}</span>
                                        </span>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing here.</p>
                                @endforelse
                                @break

                            @case('upcoming_meetings')
                                @forelse ($data as $item)
                                    <a href="{{ $item['url'] }}" class="mb-2 block text-sm hover:underline">
                                        <div class="truncate">{{ $item['title'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['date'] }} · {{ $item['time'] }}{{ ! empty($item['location']) ? ' · '.$item['location'] : '' }}</div>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming meetings.</p>
                                @endforelse
                                @break

                            @case('upcoming_deadlines')
                            @case('overdue_items')
                            @case('critical_deadlines')
                                @forelse ($data as $item)
                                    <a href="{{ $item['url'] ?? '#' }}" class="mb-2 flex items-center justify-between gap-2 text-sm hover:underline">
                                        <span class="truncate">{{ isset($item['source']) ? $item['source'].': ' : '' }}{{ $item['title'] }}</span>
                                        <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $item['due'] ?? ($item['subtitle'] ?? '') }}</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing here.</p>
                                @endforelse
                                @break

                            @case('task_distribution')
                                @forelse ($data as $slice)
                                    <div class="mb-3">
                                        <div class="mb-1 flex justify-between text-sm"><span>{{ $slice['label'] }}</span><span class="font-medium">{{ $slice['count'] ?? $slice['value'] }}</span></div>
                                        <div class="h-2 overflow-hidden rounded bg-gray-200 dark:bg-white/10"><div class="h-2 rounded bg-primary-500" style="width: {{ (int) ($slice['pct'] ?? 0) }}%"></div></div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No tasks yet.</p>
                                @endforelse
                                @break

                            @case('obligation_expiry')
                                <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                                    @foreach (['total' => 'Total', 'active' => 'Active', 'due_7' => 'Due in 7 days', 'due_30' => 'Due in 30 days', 'expired' => 'Expired'] as $key => $label)
                                        <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                        <dd class="font-medium">{{ (int) ($data[$key] ?? 0) }}</dd>
                                    @endforeach
                                </dl>
                                @break

                            @default
                                {{-- Table-shaped widgets: team workload, completion rate, department
                                     performance, today's activity. Columns come from the row keys. --}}
                                @php($tableRows = collect($data)->values())
                                @if ($tableRows->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to show.</p>
                                @else
                                    <div class="table-responsive overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                                    @foreach (array_keys((array) $tableRows->first()) as $column)
                                                        <th scope="col" class="py-1 capitalize">{{ str_replace('_', ' ', $column) }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tableRows as $tableRow)
                                                    <tr>
                                                        @foreach ((array) $tableRow as $cell)
                                                            <td class="py-1">{{ is_scalar($cell) || $cell === null ? ($cell ?? '-') : json_encode($cell) }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                        @endswitch
                    </x-filament::section>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
