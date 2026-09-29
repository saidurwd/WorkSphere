@props(['items' => [], 'label' => null])

<nav aria-label="Breadcrumb">
    <ol class="breadcrumb mb-0">
        @foreach ($items as $item)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" @if ($loop->last) aria-current="page" @endif>
                @if (! $loop->last && ! empty($item['route']))
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
                @else
                    {{ $item['label'] }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
