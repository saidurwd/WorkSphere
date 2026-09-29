@extends('layouts.guest')

@section('title', 'Forgot password')
@section('subtitle', 'We will email you a reset link')

@section('content')
@if (session('status'))
    <x-alert type="success" :message="session('status')" />
@endif

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf

    <x-form.input
        name="email"
        label="Email address"
        type="email"
        value="{{ old('email') }}"
        placeholder="you@example.com"
        autocomplete="username"
        autofocus
        required
    />

    <x-btn type="submit" variant="primary" class="w-100 justify-content-center">Email reset link</x-btn>
</form>

<p class="text-center text-body-secondary small mt-3 mb-0">
    <a href="{{ route('login') }}">Back to sign in</a>
</p>
@endsection
