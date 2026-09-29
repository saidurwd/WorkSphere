@props(['bars' => [], 'height' => 180, 'variant' => 'primary'])

@php
    $total = collect($bars)->sum('value');
@endphp

@if (count($bars))
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <span class="text-body-secondary small">Total</span>
        <span class="fs-4 fw-semibold lh-1">{{ $total }}</span>
    </div>

    <div class="border rounded-3 p-3 bg-body-secondary bg-opacity-25">
        <div class="d-flex align-items-end gap-2" style="height: {{ $height }}px;">
            @foreach ($bars as $bar)
                <div class="d-flex flex-column gap-2 flex-fill h-100" title="{{ $bar['label'] }}: {{ $bar['value'] }}">
                    <div class="position-relative flex-grow-1 d-flex align-items-end">
                        <span class="position-absolute top-0 start-0 end-0 text-center small text-body-secondary">
                            {{ $bar['value'] }}
                        </span>

                        <div class="w-100 text-bg-{{ $variant }} rounded-2"
                             style="height: {{ max($bar['pct'], 2) }}%;"></div>
                    </div>

                    <div class="small text-body-secondary text-center text-truncate">{{ $bar['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <p class="text-body-secondary mb-0">No data available.</p>
@endif
