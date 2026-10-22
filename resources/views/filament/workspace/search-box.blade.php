<form action="{{ route('filament.workspace.pages.search') }}" method="GET" role="search" class="me-4 hidden items-center md:flex">
    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass" class="w-56">
        <x-filament::input id="workspace-search" type="search" name="q" maxlength="120" autocomplete="off" aria-label="Search" />
    </x-filament::input.wrapper>
</form>
