<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section heading="Expiring in the next 90 days, by type">
                @forelse ($expiryByType as $bar)
                    <div class="mb-3">
                        <div class="mb-1 flex justify-between text-sm"><span>{{ $bar['label'] }}</span><span class="font-medium">{{ $bar['value'] }}</span></div>
                        <div class="h-2 overflow-hidden rounded bg-gray-200 dark:bg-white/10"><div class="h-2 rounded bg-primary-500" style="width: {{ $bar['pct'] }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing expires in this window.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section heading="Renewal performance">
                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">On time</dt><dd class="font-medium">{{ (int) ($renewal->on_time ?? 0) }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">Late</dt><dd class="font-medium">{{ (int) ($renewal->late ?? 0) }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">Expired</dt><dd class="font-medium">{{ (int) ($renewal->expired_count ?? 0) }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">Average lead time (days)</dt><dd class="font-medium">{{ $renewal->avg_lead_time !== null ? round((float) $renewal->avg_lead_time) : '-' }}</dd>
                </dl>
            </x-filament::section>
        </div>

        <x-filament::section heading="By department">
            <div class="table-responsive overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Department</th><th scope="col">Total</th><th scope="col">Expired</th><th scope="col">Critical</th></tr></thead>
                <tbody>
                    @forelse ($departments as $row)
                        <tr><td class="py-1">{{ $row->label }}</td><td>{{ $row->total }}</td><td>{{ (int) $row->expired }}</td><td>{{ (int) $row->critical }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-2 text-gray-500 dark:text-gray-400">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </x-filament::section>

        <x-filament::section heading="Top vendors">
            <div class="table-responsive overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Vendor</th><th scope="col">Obligations</th><th scope="col">Estimated cost</th></tr></thead>
                <tbody>
                    @forelse ($vendors as $row)
                        <tr><td class="py-1">{{ $row->label }}</td><td>{{ $row->total }}</td><td>{{ number_format((float) $row->total_cost, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-gray-500 dark:text-gray-400">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </x-filament::section>

        <x-filament::section heading="Risk register">
            <div class="table-responsive overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th scope="col" class="py-1">Obligation</th><th scope="col">Risk</th><th scope="col">Status</th><th scope="col">Expires</th></tr></thead>
                <tbody>
                    @forelse ($risk as $obligation)
                        <tr>
                            <td class="py-1"><a class="hover:underline" href="{{ \Modules\Obligations\Filament\Resources\Obligations\ObligationResource::getUrl('view', ['record' => $obligation]) }}">{{ $obligation->title }}</a></td>
                            <td>{{ ucfirst($obligation->risk_level) }}</td>
                            <td>{{ \Modules\Obligations\Filament\Resources\Obligations\ObligationResource::STATUSES[$obligation->status] ?? $obligation->status }}</td>
                            <td>{{ $obligation->expiry_date->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2 text-gray-500 dark:text-gray-400">No high-risk or lapsed obligations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
