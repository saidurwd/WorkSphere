@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <x-page-header
        title="Settings"
        subtitle="Configuration the operator can change while the application is running."
        icon="sliders">
        <a href="{{ route('admin.system.health.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-heart-pulse me-1"></i>System health
        </a>
    </x-page-header>

    <x-alert type="info" class="mb-4">
        <i class="bi bi-info-circle me-1"></i>
        Only values the application understands are accepted, and only declared types. Anything not
        listed here is either compiled in (<code>config/*.php</code>) or a deployment decision
        (<code>.env</code>) — a runtime setting that the code never reads is a setting that lies.
    </x-alert>

    <form method="POST" action="{{ route('admin.system.settings.update') }}">
        @csrf

        <div class="row g-3">
            @foreach ($groups as $group => $settings)
                <div class="col-12 col-xl-6">
                    <div class="card h-100">
                        <div class="card-header">
                            {{-- `nav-pills` as the group's own anchor list only above
                                 xl: on a narrower screen the two columns stack and a
                                 left-hand list of five group names competes with the
                                 form for the same column. --}}
                            <h2 class="card-title mb-0">{{ $group }}</h2>
                        </div>

                        <div class="card-body">
                            @foreach ($settings as $setting)
                                @php($name = 'settings['.$setting['key'].']')

                                <div class="mb-3">
                                    <label class="form-label d-flex align-items-center gap-2" for="{{ $setting['key'] }}">
                                        {{ $setting['label'] }}
                                        @if ($setting['is_overridden'])
                                            {{-- Overridden is stated rather than implied by a
                                                 different input value, so "this is the default"
                                                 and "somebody changed this" are distinguishable
                                                 at a glance. --}}
                                            <x-badge variant="info">Overridden</x-badge>
                                        @else
                                            <x-badge variant="secondary">Default</x-badge>
                                        @endif
                                    </label>

                                    @if ($setting['description'])
                                        <div class="form-text mb-1">{{ $setting['description'] }}</div>
                                    @endif

                                    @if ($setting['type'] === 'boolean')
                                        {{-- A checkbox submits NOTHING when unticked, so the
                                             hidden input carries the false. Without it the
                                             setting is impossible to switch off. --}}
                                        <input type="hidden" name="{{ $name }}" value="0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                   role="switch"
                                                   id="{{ $setting['key'] }}"
                                                   name="{{ $name }}"
                                                   value="1"
                                                   @checked((bool) $setting['value'])>
                                            <label class="visually-hidden" for="{{ $setting['key'] }}">
                                                {{ $setting['label'] }}
                                            </label>
                                        </div>
                                    @elseif ($setting['type'] === 'integer' || $setting['type'] === 'float')
                                        <input class="form-control @error($name) is-invalid @enderror"
                                               type="number"
                                               inputmode="{{ $setting['type'] === 'integer' ? 'numeric' : 'decimal' }}"
                                               @if ($setting['type'] === 'integer') step="1" @else step="any" @endif
                                               id="{{ $setting['key'] }}"
                                               name="{{ $name }}"
                                               value="{{ $setting['value'] }}">
                                    @else
                                        <input class="form-control @error($name) is-invalid @enderror"
                                               type="text"
                                               id="{{ $setting['key'] }}"
                                               name="{{ $name }}"
                                               value="{{ $setting['value'] }}"
                                               placeholder="{{ $setting['default'] }}">
                                    @endif

                                    @error($name)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    @if (! $setting['is_overridden'])
                                        <div class="form-text">
                                            Currently <code>{{ var_export($setting['default'], true) }}</code>.
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card mt-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="small text-body-secondary">
                    Changes take effect immediately. Every write records who made it.
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.system.settings.index') }}" class="btn btn-outline-secondary">
                        Discard
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save settings
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
