@extends('tyro-dashboard::layouts.admin')

@section('title', 'DLR Manage')

@section('breadcrumb')
<a href="{{ route('dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('dashboard.dlr-sync.index') }}">DLR Sync</a>
<span class="breadcrumb-separator">/</span>
<span>Manage</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">DLR Manage</h1>
            <p class="page-description">Check sync status by company, division, and month.</p>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" role="alert">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger" role="alert">
    {{ session('error') }}
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Filters</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('dashboard.dlr-sync.manage') }}" method="GET" id="filter-form">
            <input type="hidden" name="month" id="month_hidden" value="{{ old('month', $filters['month']) }}">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="flex: 0 0 calc(33.333% - 0.667rem); min-width: 200px;">
                    <label for="companycode" class="form-label">Company Code</label>
                    <select name="companycode" id="companycode" class="form-select" required>
                        <option value="">Select Company</option>
                        @foreach($companies as $code => $name)
                            <option value="{{ $code }}" {{ old('companycode', $filters['companycode']) === $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="flex: 0 0 calc(33.333% - 0.667rem); min-width: 200px;">
                    <label for="estatecode" class="form-label">Division Code</label>
                    <select name="estatecode" id="estatecode" class="form-select" required>
                        <option value="">Select Division</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 0 0 calc(33.333% - 0.667rem); min-width: 200px;">
                    <label for="month" class="form-label">Month</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <select id="month" class="form-select" required style="flex: 1;">
                            <option value="">Select Month</option>
                            @foreach(['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $num => $name)
                                <option value="{{ $num }}" {{ old('month', explode('-', $filters['month'])[1] ?? '') === $num ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <input type="number" id="year" class="form-input" value="{{ old('year', explode('-', $filters['month'])[0] ?? now()->year) }}" min="2000" max="2100" style="width: 90px;" required>
                    </div>
                </div>
                <div class="form-group" style="flex: 0 0 auto;">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Check Status
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(count($dates) > 0)
<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Sync Status ({{ count($dates) }} dates)</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $manageCompanyCode = $filters['companycode'] ?? '';
                        $manageDivisionCode = $filters['estatecode'] ?? '';
                    @endphp
                    @foreach($dates as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>
                                @if($item['status'] === 'SYNCED')
                                    <span style="color: #16a34a; font-weight: 600;">{{ $item['status'] }}</span>
                                @else
                                    <span style="color: #dc2626; font-weight: 600;">{{ $item['status'] }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('dashboard.dlr-sync.index') }}?date={{ urlencode($item['date']) }}&companycode={{ urlencode($manageCompanyCode) }}&estatecode={{ urlencode($manageDivisionCode) }}" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.875rem;">
                                    Fetch Data
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@elseif($filters['companycode'] && $filters['estatecode'] && $filters['month'])
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <h3 class="empty-state-title">No records found</h3>
            <p class="empty-state-description">The stored procedure returned no data for the selected criteria.</p>
        </div>
    </div>
</div>
@endif

<script>
    const divisions = @json($divisions);

    document.getElementById('companycode').addEventListener('change', function() {
        const companyCode = this.value;
        const divisionSelect = document.getElementById('estatecode');
        divisionSelect.innerHTML = '<option value="">Select Division</option>';

        if (companyCode && divisions[companyCode]) {
            Object.entries(divisions[companyCode]).forEach(([code, name]) => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = name;
                divisionSelect.appendChild(option);
            });
        }
    });

    document.getElementById('companycode').dispatchEvent(new Event('change'));

    const filterForm = document.getElementById('filter-form');
    const monthSelect = document.getElementById('month');
    const yearInput = document.getElementById('year');
    const monthHidden = document.getElementById('month_hidden');

    if (filterForm && monthSelect && yearInput && monthHidden) {
        filterForm.addEventListener('submit', function(e) {
            const month = monthSelect.value;
            const year = yearInput.value;

            if (!month || !year) {
                alert('Please select both month and year.');
                e.preventDefault();
                return;
            }

            monthHidden.value = year + '-' + month;
        });
    }
</script>
@endsection
