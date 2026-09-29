@props(['rows' => []])

@if (count($rows))
    <div class="d-flex flex-column gap-3">
        @foreach ($rows as $row)
            <div>
                <div class="d-flex justify-content-between gap-3 mb-1">
                    <span class="fw-semibold">{{ $row['label'] }}</span>
                    <span class="text-body-secondary small">{{ $row['value'] }}</span>
                </div>

                <div class="progress" style="height: 0.75rem;" role="progressbar"
                     aria-label="{{ $row['label'] }}" aria-valuenow="{{ $row['pct'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $row['pct'] }}%; background-color: {{ $row['color'] }};"></div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-body-secondary mb-0">No data available.</p>
@endif
