@extends('layouts.app')

@section('title', 'Edit Vendor')

@section('breadcrumb', [
    ['label' => 'Dashboard', 'url' => {{ route('dashboard.index') }}],
    ['label' => 'Obligations', 'url' => {{ route('obligations.dashboard') }}],
    ['label' => 'Vendors', 'url' => {{ route('obligations.vendors') }}],
    ['label' => 'Edit Vendor'],
])

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Vendor</h1>
            <p class="page-description">Update vendor information.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('obligations.vendors.update', $vendor) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Vendor Name <span style="color: var(--destructive);">*</span></label>
                    <input type="text" name="vendor_name" class="form-input" value="{{ old('vendor_name', $vendor->vendor_name) }}" required>
                    @error('vendor_name') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-input" value="{{ old('contact_person', $vendor->contact_person) }}">
                    @error('contact_person') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $vendor->email) }}">
                    @error('email') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="{{ old('phone', $vendor->phone) }}">
                    @error('phone') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-input" value="{{ old('website', $vendor->website) }}" placeholder="https://">
                    @error('website') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Status <span style="color: var(--destructive);">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $vendor->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $vendor->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-textarea" rows="3">{{ old('address', $vendor->address) }}</textarea>
                @error('address') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary">Update Vendor</button>
                <a href="{{ route('obligations.vendors') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
