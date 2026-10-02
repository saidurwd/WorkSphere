@extends('layouts.guest')

@section('title', 'Access Restricted')

@section('subtitle', 'Access restricted')

@section('content')
    <div class="text-center">
        <span class="d-inline-flex align-items-center justify-content-center bg-body-secondary text-danger rounded-circle mb-3"
              style="width: 5rem; height: 5rem;"
              aria-hidden="true">
            <i class="bi bi-shield-lock" style="font-size: 2.5rem;"></i>
        </span>

        <h1 class="h1 fw-bold text-danger mb-1">403</h1>
        <h2 class="h5 fw-semibold mb-3">Access Restricted</h2>

        <p class="text-body-secondary mb-4">
            You do not have permission to view this resource.
            If you believe this is an error, please contact your administrator.
        </p>

        <x-btn :href="url('/')" icon="house">Back to Dashboard</x-btn>
    </div>
@endsection
