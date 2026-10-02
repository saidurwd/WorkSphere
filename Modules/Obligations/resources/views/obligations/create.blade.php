@extends('layouts.app')

@section('title', 'New Obligation')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations', 'url' => route('obligations.index')],
        ['label' => 'New Obligation'],
    ];
@endphp

@section('content')
<x-page-header title="New Obligation" subtitle="Create a new compliance obligation." />

<div class="card">
    <div class="card-body">
        <form action="{{ route('obligations.store') }}" method="POST">
            @csrf

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="title">Title <span style="color: var(--destructive);">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required id="title">
                    @error('title') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="obligation-type-id">Obligation Type <span style="color: var(--destructive);">*</span></label>
                    <select name="obligation_type_id" class="form-select" required id="obligation-type-id">
                        <option value="">Select Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ old('obligation_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('obligation_type_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="category-id">Category <span style="color: var(--destructive);">*</span></label>
                    <select name="category_id" class="form-select" required id="category-id">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="company-id">Company <span style="color: var(--destructive);">*</span></label>
                    <select name="company_id" class="form-select" required id="company-id">
                        <option value="">Select Company</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->company_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="department-id">Department <span style="color: var(--destructive);">*</span></label>
                    <select name="department_id" class="form-select" required id="department-id">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->department_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="location-id">Location</label>
                    <select name="location_id" class="form-select" id="location-id">
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->location_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="vendor-id">Vendor</label>
                    <select name="vendor_id" class="form-select" id="vendor-id">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="owner-user-id">Owner <span style="color: var(--destructive);">*</span></label>
                    <select name="owner_user_id" class="form-select" required id="owner-user-id">
                        <option value="">Select Owner</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('owner_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('owner_user_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="backup-user-id">Backup Owner</label>
                    <select name="backup_user_id" class="form-select" id="backup-user-id">
                        <option value="">Select Backup Owner</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('backup_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('backup_user_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="reviewer-user-id">Reviewer</label>
                    <select name="reviewer_user_id" class="form-select" id="reviewer-user-id">
                        <option value="">Select Reviewer</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('reviewer_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('reviewer_user_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="approver-user-id">Approver</label>
                    <select name="approver_user_id" class="form-select" id="approver-user-id">
                        <option value="">Select Approver</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('approver_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('approver_user_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="start-date">Start Date <span style="color: var(--destructive);">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required id="start-date">
                    @error('start_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="expiry-date">Expiry Date <span style="color: var(--destructive);">*</span></label>
                    <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}" required id="expiry-date">
                    @error('expiry_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="priority">Priority <span style="color: var(--destructive);">*</span></label>
                    <select name="priority" class="form-select" required id="priority">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }} selected>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('priority') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="risk-level">Risk Level <span style="color: var(--destructive);">*</span></label>
                    <select name="risk_level" class="form-select" required id="risk-level">
                        <option value="low" {{ old('risk_level') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('risk_level') === 'medium' ? 'selected' : '' }} selected>Medium</option>
                        <option value="high" {{ old('risk_level') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('risk_level') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('risk_level') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="status">Status <span style="color: var(--destructive);">*</span></label>
                    <select name="status" class="form-select" required id="status">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }} selected>Active</option>
                        <option value="upcoming" {{ old('status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="action_required" {{ old('status') === 'action_required' ? 'selected' : '' }}>Action Required</option>
                        <option value="renewal_in_progress" {{ old('status') === 'renewal_in_progress' ? 'selected' : '' }}>Renewal In Progress</option>
                        <option value="pending_approval" {{ old('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    @error('status') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="currency">Currency</label>
                    <input type="text" name="currency" class="form-control" value="{{ old('currency', 'BDT') }}" maxlength="3" id="currency">
                    @error('currency') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="estimated-cost">Estimated Cost</label>
                    <input type="number" name="estimated_cost" class="form-control" value="{{ old('estimated_cost') }}" step="0.01" min="0" id="estimated-cost">
                    @error('estimated_cost') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3">
                    <label class="form-label" for="recurrence-type">Recurrence Type</label>
                    <select name="recurrence_type" class="form-select" id="recurrence-type">
                        <option value="">None</option>
                        <option value="monthly" {{ old('recurrence_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('recurrence_type') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="yearly" {{ old('recurrence_type') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                    @error('recurrence_type') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                <div class="col mb-3">
                    <label class="form-label" for="recurrence-interval">Recurrence Interval</label>
                    <input type="number" name="recurrence_interval" class="form-control" value="{{ old('recurrence_interval') }}" min="1" id="recurrence-interval">
                    @error('recurrence_interval') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="col mb-3" style="display: flex; gap: 1.5rem; align-items: center; padding-top: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" for="auto-renew">
                        <input type="checkbox" name="renewal_required" value="1" {{ old('renewal_required', true) ? 'checked' : '' }}>
                        <span>Renewal Required</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" for="description">
                        <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew') ? 'checked' : '' }} id="auto-renew">
                        <span>Auto Renew</span>
                    </label>
                </div>
            </div>

            <div class="mb-3" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="description-2">Description</label>
                <textarea name="description" class="form-control" rows="3" id="description" id="description-2">{{ old('description') }}</textarea>
                @error('description') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="notes">Notes</label>
                <textarea name="notes" class="form-control" rows="3" id="notes">{{ old('notes') }}</textarea>
                @error('notes') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary">Create Obligation</button>
                <a href="{{ route('obligations.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
