@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
    <x-page-header title="Vendors" subtitle="Vendors associated with compliance obligations." icon="building">
        <x-btn :href="route('obligations.vendors.create')" icon="plus-lg">New Vendor</x-btn>
    </x-page-header>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('obligations.vendors') }}" method="GET" id="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="vendor-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="vendor-search" type="search" name="search" class="form-control"
                                   placeholder="Search vendors..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="status-filter" class="form-label">Status</label>
                        <select id="status-filter" name="status" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('obligations.vendors') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if($vendors->count())
            <div class="card-body p-0">
                <x-datatable id="vendors-table" :options="['pageLength' => 20, 'order' => [[0, 'asc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Vendor Name</th>
                            <th scope="col">Contact Person</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vendors as $vendor)
                            <tr>
                                <td><strong>{{ $vendor->vendor_name }}</strong></td>
                                <td>{{ $vendor->contact_person ?? 'N/A' }}</td>
                                <td>{{ $vendor->email ?? 'N/A' }}</td>
                                <td>{{ $vendor->phone ?? 'N/A' }}</td>
                                <td>
                                    <x-badge :variant="$vendor->status === 'active' ? 'success' : 'secondary'">{{ ucfirst($vendor->status) }}</x-badge>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('obligations.vendors.edit', $vendor)" icon="pencil" label="Edit" />
                                        <form action="{{ route('obligations.vendors.destroy', $vendor) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete this vendor?"
                                                    data-confirm-button="Delete" aria-label="Delete" title="Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable>
            </div>

            @if ($vendors->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$vendors" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="building" title="No vendors found" description="No vendors have been added yet." />
            </div>
        @endif
    </div>
@endsection
