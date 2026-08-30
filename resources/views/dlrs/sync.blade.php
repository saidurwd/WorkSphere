@extends('tyro-dashboard::layouts.admin')

@section('title', 'DLR Data Sync')

@section('breadcrumb')
<a href="{{ route('dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>DLR Data Sync</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">DLR Data Sync</h1>
            <p class="page-description">Fetch DLR data from PACE360 ERP.</p>
        </div>
        <a href="{{ route('dashboard.dlr-sync.manage') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.5rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            DLR Manage
        </a>
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
        <h3 class="card-title" style="font-size: 1.0625rem;">Fetch Parameters</h3>
    </div>
    <div class="card-body">
        <form id="fetch-form">
            @csrf
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="flex: 0 0 calc(33.333% - 0.667rem); min-width: 200px;">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-input" value="{{ old('date', \Carbon\Carbon::createFromFormat('d/m/Y', $filters['date'] ?? '')->format('Y-m-d')) }}" required>
                    @error('date')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group" style="flex: 0 0 calc(33.333% - 0.667rem); min-width: 200px;">
                    <label for="companycode" class="form-label">Company Code</label>
                    <select name="companycode" id="companycode" class="form-select" required>
                        <option value="">Select Company</option>
                        @foreach($companies as $code => $name)
                        <option value="{{ $code }}" {{ old('companycode', $filters['companycode']) === $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('companycode')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group" style="flex: 0 0 calc(33.333% - 0.667rem); min-width: 200px;">
                    <label for="estatecode" class="form-label">Division Code</label>
                    <select name="estatecode" id="estatecode" class="form-select" required>
                        <option value="">Select Division</option>
                    </select>
                    @error('estatecode')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group" style="flex: 0 0 auto;">
                    <button type="button" class="btn btn-primary" id="fetch-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Fetch Data
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(count($records))
<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Fetched Records ({{ count($records) }})</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Division Name</th>
                        <th>Filter Date</th>
                        <th>Account Group Code</th>
                        <th>Account Group Desc</th>
                        <th>Cluster Code</th>
                        <th>Tag</th>
                        <th>Account Subgroup Code</th>
                        <th>Account Subgroup Desc</th>
                        <th>Hazira</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalAmount = 0.0;
                        $totalHazira = 0.0;
                        foreach ($records as $record) {
                            $amount = $record['AMOUNT'] ?? null;
                            if ($amount !== null && $amount !== '') {
                                $totalAmount += (float) $amount;
                            }
                            $hazira = $record['HAZIRA'] ?? null;
                            if ($hazira !== null && $hazira !== '') {
                                $totalHazira += (float) $hazira;
                            }
                        }
                    @endphp
                    @foreach($records as $record)
                        <tr>
                            <td>{{ $record['COMPANYNAME'] ?? '' }}</td>
                            <td>{{ $record['DIVISIONNAME'] ?? '' }}</td>
                            <td>{{ $record['FILTERDATE'] ?? '' }}</td>
                            <td>{{ $record['ACCGROUPCODE'] ?? '' }}</td>
                            <td>{{ $record['ACCGROUPDESC'] ?? '' }}</td>
                            <td>{{ $record['CLUSTERCODE'] ?? '' }}</td>
                            <td>{{ $record['TAG'] ?? '' }}</td>
                            <td>{{ $record['ACCSUBGROUPCODE'] ?? '' }}</td>
                            <td>{{ $record['ACCSUBGROUPDESC'] ?? '' }}</td>
                            <td>{{ $record['HAZIRA'] ?? '' }}</td>
                            <td>{{ $record['AMOUNT'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight: 600; background-color: #374151; color: #ffffff;">
                        <td colspan="10" style="text-align: right;">Total Hazira</td>
                        <td>{{ number_format($totalHazira, 2) }}</td>
                    </tr>
                    <tr style="font-weight: 600; background-color: #374151; color: #ffffff;">
                        <td colspan="10" style="text-align: right;">Total Amount</td>
                        <td>{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card-body" style="display: flex; justify-content: flex-end; padding-top: 1rem;">
        <form action="{{ route('dashboard.dlr-sync.sync') }}" method="POST" id="sync-form">
            @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to sync {{ count($records) }} record(s) to tblAccountInfo?')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync Data
                </button>
        </form>
    </div>
</div>
@elseif(count($records) === 0 && session('error'))
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <h3 class="empty-state-title">No records found</h3>
            <p class="empty-state-description">The API returned no data for the selected criteria.</p>
        </div>
    </div>
</div>
@endif

<script>
    const divisions = @json($divisions);
    const csrfToken = '{{ csrf_token() }}';

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

    document.getElementById('fetch-btn').addEventListener('click', function() {
        const dateInput = document.getElementById('date').value.trim();
        const companyCode = document.getElementById('companycode').value.trim();
        const estateCode = document.getElementById('estatecode').value.trim();
        const btn = this;

        if (!dateInput || !companyCode || !estateCode) {
            alert('Please select date, company, and division.');
            return;
        }

        const parts = dateInput.split('-');
        const date = parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : dateInput;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Fetching...';

        fetch('{{ route('dashboard.api.dlr.fetch') }}?date=' + encodeURIComponent(date) + '&companycode=' + encodeURIComponent(companyCode) + '&estatecode=' + encodeURIComponent(estateCode), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.href = '{{ route('dashboard.dlr-sync.index') }}?fetched=1';
            } else {
                alert('Failed: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Fetch Data
            `;
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('date') || urlParams.has('companycode') || urlParams.has('estatecode')) {
        setTimeout(() => document.getElementById('fetch-btn').click(), 500);
    }
</script>
@endsection