@props([
    'id' => 'data-table',
    'options' => [],
])

@php
    /*
     * Client-side paging is OFF by default (GAP-038).
     *
     * Every caller of this component already renders an `x-pagination` below the
     * table, so the browser was paginating a set the server had already
     * paginated: two pagers over the same page, with the DataTables one silently
     * hiding rows the server had sent. That is not a cosmetic duplication — the
     * row count a user sees disagrees with the page they are on.
     *
     * Client-side *search and sort* are kept: they operate on the page in hand,
     * which is what a user expects from them, and they do not conflict with
     * server-side paging. A view that genuinely wants client-side paging (a
     * small, fully-loaded list) can still pass `'paging' => true`.
     */
    $defaults = [
        'paging' => false,
        'info' => false,
    ];

    $settings = array_merge($defaults, $options);
@endphp

<div class="table-responsive">
    <table
        id="{{ $id }}"
        class="table table-striped table-hover align-middle w-100"
        data-dtable
        data-dtable-options="{{ json_encode($settings) }}"
    >
        {{ $slot }}
    </table>
</div>
