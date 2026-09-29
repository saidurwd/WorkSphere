@extends('layouts.guest')

@section('title', 'Reset password')
@section('subtitle', 'Choose a new password')

@section('content')
<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <x-form.input
        name="email"
        label="Email address"
        type="email"
        value="{{ old('email', $email) }}"
        autocomplete="username"
        required
    />

    <x-form.input
        name="password"
        label="New password"
        type="password"
        autocomplete="new-password"
        required
    />

    <x-form.input
        name="password_confirmation"
        label="Confirm password"
        type="password"
        autocomplete="new-password"
        required
    />

    <x-btn type="submit" variant="primary" class="w-100 justify-content-center">Reset password</x-btn>
</form>
@endsection
