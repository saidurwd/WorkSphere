@extends('layouts.app')

@section('title', 'Renew Obligation')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations', 'url' => route('obligations.index')],
        ['label' => $obligation->obligation_no, 'url' => route('obligations.show', $obligation)],
        ['label' => 'Renew'],
    ];
@endphp

@section('content')
<x-page-header title="Renew Obligation" subtitle="{{ $obligation->obligation_no }} - {{ $obligation->title }}" />

<div class="card">
    <div class="card-body">
        <form action="{{ route('obligations.renew.store', $obligation) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="previous-expiry-date">Previous Expiry Date</label>
                    <input type="text" id="previous-expiry-date" class="form-control" value="{{ $obligation->expiry_date->format('Y-m-d') }}" disabled>
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="renewal-date">Renewal Date</label>
                    <input type="text" id="renewal-date" class="form-control" value="{{ now()->format('Y-m-d') }}" disabled>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="new-start-date">New Start Date <span style="color: var(--destructive);">*</span></label>
                    {{-- The recurrence rule's computed dates take precedence; the previous
             +1 year default stays for an obligation with no rule (GAP-049). --}}
                    <input type="date" name="new_start_date" class="form-control"
                           value="{{ old('new_start_date', $suggested['start'] ?? $obligation->start_date->addYear()->format('Y-m-d')) }}" required id="new-start-date">
                    @error('new_start_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="new-expiry-date">New Expiry Date <span style="color: var(--destructive);">*</span></label>
                                        <input type="date" name="new_expiry_date" class="form-control"
                           value="{{ old('new_expiry_date', $suggested['expiry'] ?? $obligation->expiry_date->addYear()->format('Y-m-d')) }}" required id="new-expiry-date">
                    @error('new_expiry_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="vendor-id">Vendor</label>
                    <select name="vendor_id" class="form-select" id="vendor-id">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $obligation->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="cost">Cost</label>
                    <input type="number" name="cost" class="form-control" value="{{ old('cost') }}" step="0.01" min="0" id="cost">
                    @error('cost') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="purchase-reference">Purchase Reference</label>
                    <input type="text" name="purchase_reference" class="form-control" value="{{ old('purchase_reference') }}" id="purchase-reference">
                    @error('purchase_reference') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="invoice-reference">Invoice Reference</label>
                    <input type="text" name="invoice_reference" class="form-control" value="{{ old('invoice_reference') }}" id="invoice-reference">
                    @error('invoice_reference') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-3" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="document">Renewed Document</label>
                <input type="file" name="document" class="form-control" id="document">
                <small style="color: var(--muted-foreground);">Upload the renewed certificate or license document.</small>
                @error('document') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="remarks">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" id="remarks">{{ old('remarks') }}</textarea>
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
