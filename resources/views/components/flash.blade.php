@php
    $flashes = array_filter([
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ]);

    $validation = isset($errors) ? $errors->all() : [];
@endphp

@foreach ($flashes as $level => $message)
    <x-alert :type="$level" :message="$message" class="alert-dismissible fade show mb-3" />
@endforeach

@if ($validation)
    <x-alert type="danger" class="alert-dismissible fade show mb-3">
        <ul class="mb-0 ps-3">
            @foreach ($validation as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
