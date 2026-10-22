<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach (['To-Dos' => 'todos', 'Tasks' => 'tasks', 'Action items' => 'action_items', 'Obligations' => 'obligations'] as $label => $key)
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-semibold">{{ number_format($counts[$key]) }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <x-filament::section heading="Everything assigned to you">
            @if ($workItems->isEmpty() && $obligations->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nothing waiting on you. Work you are assigned to, watching, or that you created appears here.
                </p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($workItems as $row)
                        @php
                            $url = $urls[$row->source_type.':'.$row->source_id] ?? null;
                            $overdue = $row->due_date !== null
                                && \Illuminate\Support\Carbon::parse($row->due_date)->isPast()
                                && ! in_array($row->status, ['completed', 'cancelled', 'archived'], true);
                        @endphp
                        <li class="flex items-center gap-3 py-2">
                            <x-filament::badge color="gray">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $row->source_type)) }}</x-filament::badge>

                            <div class="min-w-0 flex-1">
                                {{-- Escaped: `title` comes from a user-authored record. --}}
                                <p class="truncate text-sm font-medium">
                                    @if ($url)
                                        <a href="{{ $url }}" class="hover:underline">{{ $row->title }}</a>
                                    @else
                                        {{ $row->title }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \App\Support\StatusBadge::label($row->status) }}
                                    @if ($row->due_date)
                                        · due {{ \Illuminate\Support\Carbon::parse($row->due_date)->format('M d, Y') }}
                                    @endif
                                </p>
                            </div>

                            @if ($overdue)
                                <x-filament::badge color="danger">Overdue</x-filament::badge>
                            @endif
                        </li>
                    @endforeach

                    @foreach ($obligations as $obligation)
                        <li class="flex items-center gap-3 py-2">
                            <x-filament::badge color="warning">Obligation</x-filament::badge>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    <a href="{{ route('filament.workspace.resources.obligations.view', $obligation) }}" class="hover:underline">{{ $obligation->title }}</a>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $obligation->status ?? '')) }}
                                    @if ($obligation->expiry_date)
                                        · expires {{ $obligation->expiry_date->format('M d, Y') }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
