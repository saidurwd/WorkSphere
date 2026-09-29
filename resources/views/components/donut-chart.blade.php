@props(['slices' => [], 'size' => 160, 'thickness' => 6])

@php
    $radius = 15.915;
    $offset = 25;
@endphp

<svg viewBox="0 0 42 42"
     width="{{ $size }}"
     height="{{ $size }}"
     role="img"
     aria-label="{{ collect($slices)->pluck('label')->join(', ') }} distribution"
     {{ $attributes->merge(['class' => 'flex-shrink-0']) }}>
    <circle cx="21" cy="21" r="{{ $radius }}" fill="transparent" stroke="var(--bs-border-color)" stroke-width="{{ $thickness }}"></circle>

    @foreach ($slices as $slice)
        <circle
            cx="21"
            cy="21"
            r="{{ $radius }}"
            fill="transparent"
            stroke="{{ $slice['color'] }}"
            stroke-width="{{ $thickness }}"
            stroke-dasharray="{{ $slice['pct'] }} {{ 100 - $slice['pct'] }}"
            stroke-dashoffset="{{ $offset }}"
            stroke-linecap="round"
        ></circle>

        @php($offset -= $slice['pct'])
    @endforeach
</svg>
