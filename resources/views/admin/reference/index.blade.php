@extends('layouts.app')

@section('title', $descriptor['label'])

@section('content')
    <x-page-header :title="$descriptor['label']" subtitle="Reference data used across every module."
                   :icon="$descriptor['icon']" />

    <div class="row g-3 mb-4">
        @foreach ($resources as $tab => $label)
            <div class="col-6 col-lg-3">
                <x-btn :href="route('admin.reference.index', $tab)"
                       :variant="$tab === $resource ? 'primary' : 'outline-secondary'" class="w-100">
                    {{ $label }}
                </x-btn>
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
                               placeholder="Search {{ strtolower($descriptor['label']) }}…"
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
                    @x-btn :href="route('admin.reference.create', $resource)" icon="plus-lg">New</x-btn>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if ($rows->isEmpty())
            <div class="card-body">
                <x-empty-state :icon="$descriptor['icon']"
                               :title="'No ' . strtolower($descriptor['label']) . ' yet'"
                               description="Reference data is managed here rather than with raw SQL.">
                    <x-btn :href="route('admin.reference.create', $resource)" icon="plus-lg">Create the first</x-btn>
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
                            <tr>
                                @foreach ($descriptor['fields'] as $field)
                                    <td>
                                        @php $value = $row->{$field}; @endphp
                                        @if ($value === null)
                                            <span class="text-body-secondary">&mdash;</span>
                                        @elseif ($field === 'status')
                                            <x-badge :variant="$value === 'active' ? 'success' : 'secondary'">{{ ucfirst($value) }}</x-badge>
                                        @elseif (is_numeric($value) && $value > 0 && ! str_ends_with($field, '_id') === false)
                                            {{ $options[$field]->firstWhere('id', $value)?->{str_replace('_id', '', $field).'_name'} ?? '#' . $value }}
                                        @elseif (in_array($field, ['joining_date'], true))
                                            {{ \Illuminate\Support\Carbon::parse($value)->format('M d, Y') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('admin.reference.edit', [$resource, $row->getKey()])"
                                                    icon="pencil" label="Edit {{ $row->{$descriptor['fields'][1]} ?? $row->getKey() }}" />
                                        @can('delete', \App\Models\User::class)
                                            @if ($row->status === 'active')
                                                <form action="{{ route('admin.reference.destroy', [$resource, $row->getKey()]) }}"
                                                      method="POST" class="d-inline"
                                                      data-confirm="Deactivate this record? Consumers filtering on status will stop seeing it."
                                                      data-confirm-button="Deactivate">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            aria-label="Deactivate {{ $row->{$descriptor['fields'][1]} ?? $row->getKey() }}">
                                                        <i class="bi bi-archive"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
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
