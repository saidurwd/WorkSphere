<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ($stats as $label => $value)
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-semibold">{{ number_format($value) }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach (['By type' => $typeBars, 'By priority' => $priorityBars] as $title => $bars)
                <x-filament::section :heading="$title">
                    @forelse ($bars as $bar)
                        <div class="mb-3">
                            <div class="mb-1 flex justify-between text-sm"><span>{{ $bar['label'] }}</span><span class="font-medium">{{ $bar['value'] }}</span></div>
                            <div class="h-2 overflow-hidden rounded bg-gray-200 dark:bg-white/10"><div class="h-2 rounded bg-primary-500" style="width: {{ $bar['pct'] }}%"></div></div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to show yet.</p>
                    @endforelse
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($lists as $title => $obligations)
                <x-filament::section :heading="$title">
                    @forelse ($obligations as $obligation)
                        @php($days = (int) now()->startOfDay()->diffInDays($obligation->expiry_date, false))
                        <a href="{{ \Modules\Obligations\Filament\Resources\Obligations\ObligationResource::getUrl('view', ['record' => $obligation]) }}" class="mb-2 block text-sm hover:underline">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate">{{ $obligation->title }}</span>
                                <x-filament::badge :color="match ($obligation->risk_level) { 'critical' => 'danger', 'high' => 'warning', default => 'gray' }">{{ ucfirst($obligation->risk_level) }}</x-filament::badge>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $days < 0 ? 'Expired '.abs($days).' days ago' : ($days === 0 ? 'Expires today' : $days.' days remaining') }}
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nothing here.</p>
                    @endforelse
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
