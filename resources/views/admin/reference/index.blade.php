@extends('layouts.app')

@section('title', $descriptor['label'])

@section('content')
    <x-page-header :title="$descriptor['label']"
                   subtitle="Reference data used across every module."
                   :icon="$descriptor['icon']" />

    <div class="row g-3 mb-4">
        @foreach ($resources as $tab => $label)
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.reference.index', $tab) }}"
                   class="btn w-100 {{ $tab === $resource ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.reference.index', $resource) }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="ref-search" class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input id="ref-search" type="search" name="search" class="form-control"
                               placeholder="Search {{ strtolower($descriptor['label']) }}&hellip;"
                               value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="ref-status" class="form-label">Status</label>
                    <select id="ref-status" name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.reference.create', $resource) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-plus-lg me-1"></i>New
                    </a>
                </div>
            </form>
        </div>
    </div>

    @php
        // Which column holds the display name for each foreign-key field.
        $lookupNames = [
            'department_id' => 'department_name',
            'location_id' => 'location_name',
            'head_of_department_id' => 'employee_name',
        ];

        // The column holding each row's human name, for labels.
        $nameField = $descriptor['fields'][1] ?? 'id';
    @endphp

    <div class="card">
        @if ($rows->isEmpty())
            <div class="card-body">
                <x-empty-state :icon="$descriptor['icon']"
                               :title="'No ' . strtolower($descriptor['label']) . ' yet'"
                               description="Reference data is managed here rather than with raw SQL.">
                    <a href="{{ route('admin.reference.create', $resource) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-plus-lg me-1"></i>Create the first
                    </a>
                </x-empty-state>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach ($descriptor['fields'] as $field)
                                <th scope="col">{{ \Illuminate\Support\Str::headline($field) }}</th>
                            @endforeach
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php $label = (string) ($row->{$nameField} ?? $row->getKey()); @endphp
                            <tr>
                                @foreach ($descriptor['fields'] as $field)
                                    @php
                                        $value = $row->{$field};

                                        // Resolve a foreign key to its display name
                                        // here rather than with a dynamic `?->{...}`
                                        // access, which does not compile.
                                        $display = $value;
                                        $isJoin = false;

                                        if ($value !== null && str_ends_with($field, '_id') && isset($options[$field])) {
                                            $match = $options[$field]->firstWhere('id', $value);
                                            $display = $match->{$lookupNames[$field]} ?? '#'.$value;
                                            $isJoin = true;
                                        }
                                    @endphp

                                    <td>
                                        @if ($value === null)
                                            <span class="text-body-secondary">&mdash;</span>
                                        @elseif ($field === 'status')
                                            <x-badge :variant="$value === 'active' ? 'success' : 'secondary'">
                                                {{ ucfirst($value) }}
                                            </x-badge>
                                        @elseif ($isJoin)
                                            {{ $display }}
                                        @elseif ($field === 'joining_date')
                                            {{ \Illuminate\Support\Carbon::parse($value)->format('M d, Y') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach

                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('admin.reference.edit', [$resource, $row->getKey()])"
                                                    icon="pencil" label="Edit {{ $label }}" />

                                        @if ($row->status === 'active')
                                            <form action="{{ route('admin.reference.destroy', [$resource, $row->getKey()]) }}"
                                                  method="POST" class="d-inline"
                                                  data-confirm="Deactivate this record? Consumers filtering on status will stop seeing it."
                                                  data-confirm-button="Deactivate">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        aria-label="Deactivate {{ $label }}">
                                                    <i class="bi bi-archive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($rows->hasPages())
                <div class="card-footer"><x-pagination :paginator="$rows" /></div>
            @endif
        @endif
    </div>
@endsection
