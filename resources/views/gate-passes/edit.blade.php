@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Gate Pass')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('gate-passes.index') }}">Gate Passes</a>
<span class="breadcrumb-separator">/</span>
<span>Edit Gate Pass</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Gate Pass</h1>
            <p class="page-description">Update details for {{ $gatePass->gate_pass_number }}.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('gate-passes.update', $gatePass) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Pass Number</label>
                <input type="text" class="form-input" value="{{ $gatePass->gate_pass_number }}" disabled>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $gatePass->name) }}" required>
                    @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="issue_date" class="form-label">Issue Date <span class="text-red-500">*</span></label>
                    <input type="date" name="issue_date" id="issue_date" class="form-input" value="{{ old('issue_date', $gatePass->issue_date->format('Y-m-d')) }}" required>
                    @error('issue_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-input" value="{{ old('quantity', $gatePass->quantity) }}" min="0">
                    @error('quantity') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="prepared_by" class="form-label">Prepared By <span class="text-red-500">*</span></label>
                    <select name="prepared_by" id="prepared_by" class="form-select" required>
                        @foreach($users as $user)
                            <option value="{{ $user->name }}" {{ old('prepared_by', $gatePass->prepared_by) === $user->name ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('prepared_by') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="checked_by" class="form-label">Checked By</label>
                    <select name="checked_by" id="checked_by" class="form-select">
                        <option value="">Not Checked</option>
                        @foreach($users as $user)
                            <option value="{{ $user->name }}" {{ old('checked_by', $gatePass->checked_by) === $user->name ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('checked_by') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" name="address" id="address" class="form-input" value="{{ old('address', $gatePass->address) }}">
                    @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="purpose" class="form-label">Purpose <span class="text-red-500">*</span></label>
                <textarea name="purpose" id="purpose" class="form-textarea" rows="3" required>{{ old('purpose', $gatePass->purpose) }}</textarea>
                @error('purpose') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-textarea" rows="3">{{ old('description', $gatePass->description) }}</textarea>
                @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="{{ route('gate-passes.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Gate Pass</button>
            </div>
        </form>
    </div>
</div>
@endsection
