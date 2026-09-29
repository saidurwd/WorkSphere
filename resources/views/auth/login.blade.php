@extends('layouts.guest')

@section('title', 'Sign in')
@section('subtitle', 'Sign in to your workspace')

@section('content')
<form method="POST" action="{{ route('login') }}" novalidate>
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

    <x-form.input
        name="password"
        label="Password"
        type="password"
        placeholder="••••••••"
        autocomplete="current-password"
        required
    />

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="form-check">
            <input type="checkbox" name="remember" value="1" id="remember" class="form-check-input" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>

        <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
    </div>

    <x-btn type="submit" variant="primary" class="w-100 justify-content-center">Sign in</x-btn>
</form>
@endsection
