<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <form wire:submit="runSearch" role="search" class="space-y-4">
                <div class="flex gap-2">
                    <div class="flex-1">
                        <x-filament::input.wrapper>
                            <x-filament::input type="search" wire:model="term" maxlength="120" placeholder="Type at least two characters…" aria-label="Search for" autocomplete="off" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button type="submit">Search</x-filament::button>
                </div>

                @error('term') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                @error('modules.*') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror

                <div class="grid gap-4 md:grid-cols-4">
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">Status</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="status">
                                <option value="">Any</option>
                                @foreach ($statusValues as $value)
                                    <option value="{{ $value }}">{{ \App\Support\StatusBadge::label($value) }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">Owner</span>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="owner">
                                <option value="">Anyone</option>
                                @foreach ($owners as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">From</span>
                        <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block font-medium">To</span>
                        <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
                    </label>
                </div>

                <fieldset class="flex flex-wrap gap-4 text-sm">
                    <legend class="mb-1 font-medium">Modules</legend>
                    @foreach ($entities as $key => $entity)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="modules" value="{{ $key }}" class="rounded border-gray-300">
                            {{ $entity->label }}
                        </label>
                    @endforeach
                </fieldset>
            </form>
        </x-filament::section>

        @if ($term === '')
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing searched yet. Titles and descriptions are searched, and results are limited to what you are permitted to see.</p>
            </x-filament::section>
        @elseif ($groups->isEmpty())
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing matched “{{ $term }}”.</p>
            </x-filament::section>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} for “{{ $term }}”.</p>

            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($groups as $group)
                    @php($entity = $group['entity'])
                    <x-filament::section :heading="$entity->label.' ('.$group['count'].')'">
                        <ul class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($group['rows'] as $row)
                                @php($url = $entity->urlFor($row))
                                <li class="py-2 text-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        @if ($url)
                                            <a href="{{ $url }}" class="font-medium hover:underline">{{ $row->{$entity->titleColumn} }}</a>
                                        @else
                                            <span class="font-medium">{{ $row->{$entity->titleColumn} }}</span>
                                        @endif
                                        <span class="flex shrink-0 items-center gap-2">
                                            @if ($entity->statusColumn && $row->{$entity->statusColumn} !== null)
                                                <x-filament::badge color="gray">{{ \App\Support\StatusBadge::label($row->{$entity->statusColumn}) }}</x-filament::badge>
                                            @endif
                                            @if ($entity->dateColumn && $row->{$entity->dateColumn} !== null)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($row->{$entity->dateColumn})->format('M d, Y') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    {{-- The excerpt is escaped by SearchableEntity::excerpt() before the
                                         highlight wrapper is added, so printing it raw is safe. --}}
                                    @php($excerpt = $entity->excerpt($row, $term))
                                    @if ($excerpt !== '')
                                        <div class="mt-1 text-gray-600 dark:text-gray-300">{!! $excerpt !!}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
