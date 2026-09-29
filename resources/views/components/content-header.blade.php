@php
    $breadcrumbs = $__env->getSections()['breadcrumb'] ?? null;
    $isArray = is_array($breadcrumbs);
    $hasCrumbs = $isArray ? $breadcrumbs !== [] : filled($breadcrumbs);
    $hasActions = $__env->hasSection('header-actions');
@endphp

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-start mb-0">
                    @if ($isArray)
                        @foreach ($breadcrumbs as $crumb)
                            @php
                                $isLast = $loop->last;
                                $url = $crumb['url'] ?? null;
                            @endphp

                            <li class="breadcrumb-item {{ $isLast ? 'active' : '' }}"
                                @if ($isLast) aria-current="page" @endif>
                                @if (! $isLast && $url)
                                    <a href="{{ $url }}">{{ $crumb['label'] ?? '' }}</a>
                                @else
                                    {{ $crumb['label'] ?? '' }}
                                @endif
                            </li>
                        @endforeach
                    @elseif ($hasCrumbs)
                        {!! $breadcrumbs !!}
                    @else
                        <li class="breadcrumb-item active" aria-current="page">@yield('title')</li>
                    @endif
                </ol>
            </div>

            @if ($hasActions)
                <div class="col-sm-6">
                    <div class="float-sm-end">
                        @yield('header-actions')
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
