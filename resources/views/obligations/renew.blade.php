@extends('tyro-dashboard::layouts.admin')

@section('title', 'Renew Obligation')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.index') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.show', $obligation) }}">{{ $obligation->obligation_no }}</a>
<span class="breadcrumb-separator">/</span>
<span>Renew</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Renew Obligation</h1>
            <p class="page-description">{{ $obligation->obligation_no }} - {{ $obligation->title }}</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('obligations.renew.store', $obligation) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Previous Expiry Date</label>
                    <input type="text" class="form-input" value="{{ $obligation->expiry_date->format('Y-m-d') }}" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">Renewal Date</label>
                    <input type="text" class="form-input" value="{{ now()->format('Y-m-d') }}" disabled>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">New Start Date <span style="color: var(--destructive);">*</span></label>
                    <input type="date" name="new_start_date" class="form-input" value="{{ old('new_start_date', $obligation->start_date->addYear()->format('Y-m-d')) }}" required>
                    @error('new_start_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">New Expiry Date <span style="color: var(--destructive);">*</span></label>
                    <input type="date" name="new_expiry_date" class="form-input" value="{{ old('new_expiry_date', $obligation->expiry_date->addYear()->format('Y-m-d')) }}" required>
                    @error('new_expiry_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $obligation->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Cost</label>
                    <input type="number" name="cost" class="form-input" value="{{ old('cost') }}" step="0.01" min="0">
                    @error('cost') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Purchase Reference</label>
                    <input type="text" name="purchase_reference" class="form-input" value="{{ old('purchase_reference') }}">
                    @error('purchase_reference') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Invoice Reference</label>
                    <input type="text" name="invoice_reference" class="form-input" value="{{ old('invoice_reference') }}">
                    @error('invoice_reference') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Renewed Document</label>
                <input type="file" name="document" class="form-input">
                <small style="color: var(--muted-foreground);">Upload the renewed certificate or license document.</small>
                @error('document') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-input" rows="3">{{ old('remarks') }}</textarea>
                @error('remarks') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary">Complete Renewal</button>
                <a href="{{ route('obligations.show', $obligation) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
