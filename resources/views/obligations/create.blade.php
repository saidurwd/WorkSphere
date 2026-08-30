@extends('tyro-dashboard::layouts.admin')

@section('title', 'New Obligation')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.index') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>New Obligation</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">New Obligation</h1>
            <p class="page-description">Create a new compliance obligation.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('obligations.store') }}" method="POST">
            @csrf

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Title <span style="color: var(--destructive);">*</span></label>
                    <input type="text" name="title" class="form-input" value="{{ old('title') }}" required>
                    @error('title') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Obligation Type <span style="color: var(--destructive);">*</span></label>
                    <select name="obligation_type_id" class="form-select" required>
                        <option value="">Select Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ old('obligation_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('obligation_type_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Category <span style="color: var(--destructive);">*</span></label>
                    <select name="category_id" class="form-select" required>
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

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Company <span style="color: var(--destructive);">*</span></label>
                    <select name="company_id" class="form-select" required>
                        <option value="">Select Company</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->company_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Department <span style="color: var(--destructive);">*</span></label>
                    <select name="department_id" class="form-select" required>
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

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->location_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
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

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Owner <span style="color: var(--destructive);">*</span></label>
                    <select name="owner_user_id" class="form-select" required>
                        <option value="">Select Owner</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('owner_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('owner_user_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Backup Owner</label>
                    <select name="backup_user_id" class="form-select">
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

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Reviewer</label>
                    <select name="reviewer_user_id" class="form-select">
                        <option value="">Select Reviewer</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('reviewer_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('reviewer_user_id') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Approver</label>
                    <select name="approver_user_id" class="form-select">
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

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Start Date <span style="color: var(--destructive);">*</span></label>
                    <input type="date" name="start_date" class="form-input" value="{{ old('start_date') }}" required>
                    @error('start_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Date <span style="color: var(--destructive);">*</span></label>
                    <input type="date" name="expiry_date" class="form-input" value="{{ old('expiry_date') }}" required>
                    @error('expiry_date') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Priority <span style="color: var(--destructive);">*</span></label>
                    <select name="priority" class="form-select" required>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }} selected>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('priority') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Risk Level <span style="color: var(--destructive);">*</span></label>
                    <select name="risk_level" class="form-select" required>
                        <option value="low" {{ old('risk_level') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('risk_level') === 'medium' ? 'selected' : '' }} selected>Medium</option>
                        <option value="high" {{ old('risk_level') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('risk_level') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('risk_level') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Status <span style="color: var(--destructive);">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }} selected>Active</option>
                        <option value="upcoming" {{ old('status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="action_required" {{ old('status') === 'action_required' ? 'selected' : '' }}>Action Required</option>
                        <option value="renewal_in_progress" {{ old('status') === 'renewal_in_progress' ? 'selected' : '' }}>Renewal In Progress</option>
                        <option value="pending_approval" {{ old('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    @error('status') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-input" value="{{ old('currency', 'BDT') }}" maxlength="3">
                    @error('currency') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Estimated Cost</label>
                    <input type="number" name="estimated_cost" class="form-input" value="{{ old('estimated_cost') }}" step="0.01" min="0">
                    @error('estimated_cost') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Recurrence Type</label>
                    <select name="recurrence_type" class="form-select">
                        <option value="">None</option>
                        <option value="monthly" {{ old('recurrence_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('recurrence_type') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="yearly" {{ old('recurrence_type') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                    @error('recurrence_type') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Recurrence Interval</label>
                    <input type="number" name="recurrence_interval" class="form-input" value="{{ old('recurrence_interval') }}" min="1">
                    @error('recurrence_interval') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="display: flex; gap: 1.5rem; align-items: center; padding-top: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="renewal_required" value="1" {{ old('renewal_required', true) ? 'checked' : '' }}>
                        <span>Renewal Required</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew') ? 'checked' : '' }}>
                        <span>Auto Renew</span>
                    </label>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3">{{ old('description') }}</textarea>
                @error('description') <span style="color: var(--destructive); font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3">{{ old('notes') }}</textarea>
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
