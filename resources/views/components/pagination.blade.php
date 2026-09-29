@props(['paginator'])

@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="mt-3">
        {{ $paginator->onEachSide(1)->links() }}
    </nav>
@endif
