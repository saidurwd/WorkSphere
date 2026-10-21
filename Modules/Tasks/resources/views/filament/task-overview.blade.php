<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach (['Total tasks' => $total, 'Completed' => $completed, 'Pending' => $pending] as $label => $value)
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-semibold">{{ number_format($value) }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ([
                'By status' => $statusBars,
                'By priority' => $priorityBars,
                'Created this week' => $weeklyBars,
                'Top projects' => $projectBars,
            ] as $title => $bars)
                <x-filament::section :heading="$title">
                    @forelse ($bars as $bar)
                        <div class="mb-3">
                            <div class="mb-1 flex justify-between text-sm">
                                <span>{{ $bar['label'] }}</span>
                                <span class="font-medium">{{ $bar['value'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded bg-gray-200 dark:bg-white/10">
                                <div class="h-2 rounded bg-primary-500" style="width: {{ $bar['pct'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to show yet.</p>
                    @endforelse
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($lists as $title => $tasks)
                <x-filament::section :heading="$title">
                    @forelse ($tasks as $task)
                        <a href="{{ \Modules\Tasks\Filament\Resources\Tasks\TaskResource::getUrl('view', ['record' => $task]) }}"
                           class="mb-2 flex items-center justify-between gap-3 text-sm hover:underline">
                            <span class="truncate">{{ $task->title }}</span>
                            <span class="flex shrink-0 items-center gap-2">
                                <x-filament::badge :color="match ($task->priority) { 'high' => 'danger', 'medium' => 'primary', default => 'gray' }">{{ ucfirst($task->priority) }}</x-filament::badge>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $task->due_date?->format('M d') }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nothing here.</p>
                    @endforelse
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
