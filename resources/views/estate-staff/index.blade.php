@extends('tyro-dashboard::layouts.admin')

@section('title', 'Estate Staff')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Estate Staff</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Estate Staff</h1>
            <p class="page-description">Manage estate staff records.</p>
        </div>
        <div>
            <a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('resources.create'), 'estate_staff') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Staff
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('estate-staff.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search by name, PF no, quarter..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Estate:</label>
                    <select name="estate_id" id="estate-filter" class="form-select" style="min-width: 180px;">
                        <option value="">All Estates</option>
                        @foreach($estates as $estate)
                            <option value="{{ $estate->id }}" {{ ($filters['estate_id'] ?? '') === (string) $estate->id ? 'selected' : '' }}>
                                {{ $estate->estate_name_eng }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Division:</label>
                    <select name="division_id" id="division-filter" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Divisions</option>
                    </select>
                </div>

                <script>
                    const estateFilter = document.getElementById('estate-filter');
                    const divisionFilter = document.getElementById('division-filter');
                    const selectedDivision = '{{ $filters['division_id'] ?? '' }}';
                    const divisionsJson = @json($estateDivisions);

                    function populateDivisions(estateId, preserveSelected = true) {
                        const divisions = estateId && divisionsJson[estateId]
                            ? divisionsJson[estateId]
                            : [];

                        divisionFilter.innerHTML = '<option value="">All Divisions</option>';

                        divisions.forEach(function (division) {
                            const option = document.createElement('option');
                            option.value = division.id;
                            option.textContent = division.division_name_eng;
                            if (preserveSelected && division.id === selectedDivision) {
                                option.selected = true;
                            }
                            divisionFilter.appendChild(option);
                        });
                    }

                    function filterAndSubmit() {
                        const estateId = estateFilter.value;
                        populateDivisions(estateId, false);
                        document.getElementById('filter-form').submit();
                    }

                    estateFilter.addEventListener('change', filterAndSubmit);

                    const initialEstateId = estateFilter.value;
                    if (initialEstateId) {
                        populateDivisions(initialEstateId, true);
                    }
                </script>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty($filters['search']) || !empty($filters['estate_id']) || !empty($filters['division_id']))
                    <a href="{{ route('estate-staff.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($estateStaffs->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Estate</th>
                        <th>Division</th>
                        <th>Staff Name</th>
                        <th>PF Number</th>
                        <th>Quarter Code</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estateStaffs as $staff)
                    <tr>
                        <td>{{ $staff->estate?->estate_name_eng ?? '-' }}</td>
                        <td>{{ $staff->division?->division_name_eng ?? '-' }}</td>
                        <td>
                            <span style="font-weight: 500;">{{ $staff->staff_name }}</span>
                        </td>
                        <td>{{ $staff->pf_number }}</td>
                        <td>{{ $staff->quarter_code }}</td>
                        <td>
                            <div class="table-actions" style="justify-content: flex-end;">
                                <a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('resources.show'), ['estate_staff', $staff->id]) }}" class="btn btn-icon btn-ghost" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('estate-staff.print', $staff) }}" class="btn btn-icon btn-ghost" title="Print" target="_blank">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                                    </svg>
                                </a>
                                <a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('resources.edit'), ['estate_staff', $staff->id]) }}" class="btn btn-icon btn-ghost" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('resources.destroy'), ['estate_staff', $staff->id]) }}" method="POST" style="display: inline;" id="delete-resource-item-form-{{ $staff->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-icon btn-ghost text-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this item?')) { document.getElementById('delete-resource-item-form-{{ $staff->id }}').submit(); }">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($estateStaffs->hasPages())
        <div class="pagination">
            {{ $estateStaffs->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="empty-state-title">No estate staff found</h3>
            <p class="empty-state-description">Get started by creating a new estate staff record.</p>
            <a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('resources.create'), 'estate_staff') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Staff
            </a>
        </div>
    @endif
</div>
@endsection
