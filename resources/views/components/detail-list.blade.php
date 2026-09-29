@props(['items' => []])

<dl class="row mb-0">
    @foreach ($items as $item)
        <dt class="col-sm-4 text-body-secondary fw-normal">{{ $item['label'] }}</dt>
        <dd class="col-sm-8 mb-3">{{ $item['value'] ?? '—' }}</dd>
    @endforeach
</dl>
