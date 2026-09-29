@props(['slices' => [], 'total' => null])

@if (count($slices))
    <div {{ $attributes->merge(['class' => 'd-flex flex-column gap-2']) }}>
        @foreach ($slices as $slice)
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="flex-shrink-0 rounded-circle"
                          style="width: 0.65rem; height: 0.65rem; background: {{ $slice['color'] }};"></span>
                    <span class="text-truncate">{{ $slice['label'] }}</span>
                </div>

                <div class="text-body-secondary text-nowrap">
                    {{ $slice['count'] }} ({{ $slice['pct'] }}%)
                </div>
            </div>
        @endforeach

        @if (! is_null($total))
            <div class="d-flex align-items-center justify-content-between border-top pt-2 mt-1">
                <span class="text-body-secondary">Total</span>
                <span class="fw-semibold">{{ $total }}</span>
            </div>
        @endif
    </div>
@else
    <p class="text-body-secondary mb-0">No data available.</p>
@endif
