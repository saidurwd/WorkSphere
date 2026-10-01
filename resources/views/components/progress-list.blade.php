@props(['rows' => []])

{{-- Each row: ['label' => string, 'value' => string, 'pct' => int, 'color' => ?string].

     `pct` and `color` are read with defaults rather than assumed: this component is
     shared by every report screen, and an undefined-key warning here takes down
     the whole page over a cosmetic bar width. --}}

@if (count($rows))
    <div class="d-flex flex-column gap-3">
        @foreach ($rows as $row)
            @php
                $pct = max(0, min(100, (int) ($row['pct'] ?? 0)));
                $color = $row['color'] ?? null;
            @endphp
            <div>
                <div class="d-flex justify-content-between gap-3 mb-1">
                    <span class="fw-semibold">{{ $row['label'] ?? '—' }}</span>
                    <span class="text-body-secondary small">{{ $row['value'] ?? '' }}</span>
                </div>

                <div class="progress" style="height: 0.75rem;" role="progressbar"
                     aria-label="{{ $row['label'] ?? 'Progress' }}" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $pct }}%{{ $color ? '; background-color: '.$color : '' }};"></div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-body-secondary mb-0">No data available.</p>
@endif
