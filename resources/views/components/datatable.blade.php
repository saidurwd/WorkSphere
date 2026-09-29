@props([
    'id' => 'data-table',
    'options' => [],
])

<div class="table-responsive">
    <table
        id="{{ $id }}"
        class="table table-striped table-hover align-middle w-100"
        data-dtable
        data-dtable-options="{{ json_encode($options) }}"
    >
        {{ $slot }}
    </table>
</div>
