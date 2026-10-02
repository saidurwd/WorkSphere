@extends('layouts.app')

@section('title', 'Feature Flags')

@section('content')
    <x-page-header
        title="Feature Flags"
        subtitle="Turn behaviour on without a deploy. Roll out gradually, target by role, delete when the rollout is done."
        icon="toggle2-on">
        <span id="flagRouteBase" data-base="{{ route('admin.system.flags.index') }}" hidden></span>

        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newFlag">
            <i class="bi bi-plus-lg me-1"></i>New flag
        </button>
    </x-page-header>

    <x-alert type="info">
        A flag is expected to be temporary. When a rollout finishes, delete the flag: every call site
        then falls back to the default it states in code, which is the state you want the code to be
        in. Leaving flags behind is how a temporary switch becomes permanent configuration nobody can
        find.
    </x-alert>

    @if ($flags === [])
        <div class="card">
            <div class="card-body">
                <x-empty-state
                    icon="toggle2-off"
                    title="No feature flags"
                    description="Nothing is being rolled out. Create one when a change needs to reach production in stages.">

        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newFlag">
                        Create the first flag
                    </button>
                </x-empty-state>
            </div>
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <caption class="visually-hidden">Feature flags and their rollout state</caption>
                    <thead>
                        <tr>
                            <th scope="col">Flag</th>
                            <th scope="col">Value</th>
                            <th scope="col" class="text-center">Rollout</th>
                            <th scope="col">Targeting</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($flags as $flag)
                            <tr>
                                <th scope="row" class="fw-semibold">
                                    <code>{{ $flag['key'] }}</code>
                                    <div class="fw-normal small text-body-secondary">
                                        {{ $flag['name'] ?? '' }}
                                    </div>
                                    @if (! empty($flag['description']))
                                        <div class="fw-normal small text-body-secondary">
                                            {{ $flag['description'] }}
                                        </div>
                                    @endif
                                </th>

                                <td>
                                    {{-- State is text first. A row that distinguishes on/off by
                                         colour alone is unreadable to a colour-blind operator
                                         and invisible in a printed incident record. --}}
                                    @if ($flag['is_enabled'])
                                        <x-badge variant="success">On</x-badge>
                                    @else
                                        <x-badge variant="secondary">Off</x-badge>
                                    @endif
                                    @if (($flag['type'] ?? 'boolean') !== 'boolean')
                                        <x-badge variant="info" class="ms-1">{{ $flag['type'] }}</x-badge>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @php($percentage = (int) ($flag['rollout_percentage'] ?? 100))
                                    <span class="small">{{ $percentage }}%</span>
                                    <div class="progress mt-1" style="height: 4px;" role="presentation">
                                        <div class="progress-bar bg-secondary"
                                             style="width: {{ $percentage }}%"
                                             aria-hidden="true"></div>
                                    </div>
                                    <span class="visually-hidden">{{ $percentage }} percent of users</span>
                                </td>

                                <td>
                                    @if (empty($flag['target_roles']))
                                        <span class="text-body-secondary small">Everyone</span>
                                    @else
                                        @foreach ($flag['target_roles'] as $role)
                                            <x-badge variant="secondary" class="me-1">{{ $role }}</x-badge>
                                        @endforeach
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions for {{ $flag['key'] }}">
                                        <form method="POST"
                                              action="{{ route('admin.system.flags.toggle', $flag['key']) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn {{ $flag['is_enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                    data-confirm="{{ $flag['is_enabled'] ? 'Turn this flag OFF for everyone?' : 'Turn this flag ON?' }}">
                                                {{ $flag['is_enabled'] ? 'Turn off' : 'Turn on' }}
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editFlag"
                                                data-flag='@json([
                                                    "key" => $flag["key"],
                                                    "name" => $flag["name"],
                                                    "description" => $flag["description"],
                                                    "type" => $flag["type"],
                                                    "value" => $flag["value"],
                                                    "rollout" => (int) $flag["rollout_percentage"],
                                                    "roles" => $flag["target_roles"] ?? [],
                                                ])'
                                                aria-label="Edit {{ $flag['key'] }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form method="POST"
                                              action="{{ route('admin.system.flags.destroy', $flag['key']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger"
                                                    data-confirm="Delete this flag? Every call site falls back to its stated default."
                                                    aria-label="Delete {{ $flag['key'] }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Create --}}
    <x-modal id="newFlag" title="New feature flag">
        <form method="POST" action="{{ route('admin.system.flags.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="flag-key">Key <span class="text-danger">*</span></label>
                <input class="form-control @error('key') is-invalid @enderror" type="text" id="flag-key"
                       name="key" value="{{ old('key') }}" required placeholder="meetings.templates">
                <div class="form-text">
                    Lowercase letters, numbers, dots, dashes or underscores. This becomes an attribute
                    on the flag object, so keep it short.
                </div>
                @error('key')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="flag-name">Name</label>
                <input class="form-control" type="text" id="flag-name" name="name" value="{{ old('name') }}">
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="flag-description">Description</label>
                <textarea class="form-control" id="flag-description" name="description" rows="2">{{ old('description') }}</textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label" for="flag-type">Type</label>
                    <select class="form-select" id="flag-type" name="type">
                        @foreach (['boolean', 'integer', 'float', 'string', 'json'] as $type)
                            <option value="{{ $type }}" @selected(old('type', 'boolean') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="flag-rollout">Rollout %</label>
                    <input class="form-control" type="number" id="flag-rollout" name="rollout_percentage"
                           min="0" max="100" value="{{ old('rollout_percentage', 100) }}">
                    <div class="form-text">Below 100 puts only that share of users in the canary.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="flag-value">Value when on</label>
                <input class="form-control" type="text" id="flag-value" name="value"
                       value="{{ old('value', 'true') }}">
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create flag</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit, populated from the row's button --}}
    <x-modal id="editFlag" title="Edit feature flag">
        <form method="POST" id="editFlagForm">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label" for="edit-flag-name">Name</label>
                <input class="form-control" type="text" id="edit-flag-name" name="name">
            </div>

            <div class="mb-3">
                <label class="form-label" for="edit-flag-description">Description</label>
                <textarea class="form-control" id="edit-flag-description" name="description" rows="2"></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label" for="edit-flag-type">Type</label>
                    <select class="form-select" id="edit-flag-type" name="type">
                        @foreach (['boolean', 'integer', 'float', 'string', 'json'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="edit-flag-rollout">Rollout %</label>
                    <input class="form-control" type="number" id="edit-flag-rollout" name="rollout_percentage"
                           min="0" max="100">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="edit-flag-value">Value when on</label>
                <input class="form-control" type="text" id="edit-flag-value" name="value">
            </div>

            <div class="mb-3">
                <label class="form-label" for="edit-flag-roles">Target roles</label>
                <input class="form-control" type="text" id="edit-flag-roles" name="target_roles_raw"
                       placeholder="admin, manager">
                <div class="form-text">
                    Comma separated. Leave blank for everyone. A flag aimed at a role is never even
                    resolved for anyone else, so it does not reveal itself.
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </x-modal>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('editFlagForm');
            var roles = document.getElementById('edit-flag-roles');

            if (! form) {
                return;
            }

            document.querySelectorAll('[data-flag]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var flag = JSON.parse(button.dataset.flag);

                    var base = document.getElementById('flagRouteBase').dataset.base;

                    // Trailing slash so the key appends to the index URL rather
                    // than replacing its last segment.
                    form.action = base.replace(/\/$/, '') + '/' + encodeURIComponent(flag.key);
                    document.getElementById('edit-flag-name').value = flag.name || '';
                    document.getElementById('edit-flag-description').value = flag.description || '';
                    document.getElementById('edit-flag-type').value = flag.type || 'boolean';
                    document.getElementById('edit-flag-rollout').value = flag.rollout ?? 100;
                    document.getElementById('edit-flag-value').value = flag.value ?? true;
                    roles.value = (flag.roles || []).join(', ');
                });
            });
        })();
    </script>
@endpush
