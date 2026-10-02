@extends('layouts.app')

@section('title', 'New Vendor')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations', 'url' => route('obligations.dashboard')],
        ['label' => 'Vendors', 'url' => route('obligations.vendors')],
        ['label' => 'New Vendor'],
    ];
@endphp

@section('content')
<x-page-header title="New Vendor" subtitle="Add a new vendor for obligation management." />

<div class="card">
    <div class="card-body">
        <form action="{{ route('obligations.vendors.store') }}" method="POST">
            @csrf

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="vendor-name">Vendor Name <span style="color: var(--destructive);">*</span></label>
                    <input type="text" name="vendor_name" class="form-control" value="{{ old('vendor_name') }}" required id="vendor-name">
                    @error('vendor_name') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="contact-person">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}" id="contact-person">
                    @error('contact_person') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" id="email">
                    @error('email') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" id="phone">
                    @error('phone') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="website">Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website') }}" placeholder="https://" id="website">
                    @error('website') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="status">Status <span style="color: var(--destructive);">*</span></label>
                    <select name="status" class="form-select" required id="status">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-3" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="address">Address</label>
                <textarea name="address" class="form-control" rows="3" id="address">{{ old('address') }}</textarea>
                @error('address') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary">Save Vendor</button>
                <a href="{{ route('obligations.vendors') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
