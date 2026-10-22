<form action="{{ route('filament.workspace.pages.search') }}" method="GET" role="search" class="me-4 hidden items-center md:flex">
    <label for="workspace-search" class="sr-only">Search</label>
    <x-filament::input.wrapper class="w-64">
        <x-filament::input id="workspace-search" type="search" name="q" maxlength="120" placeholder="Search…" autocomplete="off" />
    </x-filament::input.wrapper>
</form>
