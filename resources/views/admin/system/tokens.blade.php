@extends('layouts.app')

@section('title', 'API Tokens')

@section('content')
    <x-page-header
        title="API Tokens"
        subtitle="Standing credentials for integrations, and how to revoke them."
        icon="key">
        <a href="{{ url('/api/v1') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-code me-1"></i>API reference
        </a>
    </x-page-header>

    <x-alert type="warning">
        <strong>Tokens are created over the API, not here.</strong>
        Issuing a token is an escalation to standing programmatic access, so it stays on the audited
        path where the abilities and the expiry are both constrained, and where every grant is
        recorded against your account. This screen is for reviewing and revoking what already exists.
    </x-alert>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <x-stat title="Active tokens" :value="$tokens->total()" icon="key" />
        </div>
        <div class="col-12 col-md-6">
            <x-stat
                title="Token lifetime"
                :value="$expiryMinutes > 0 ? intdiv($expiryMinutes, 60).'h' : 'No expiry'"
                icon="clock"
                variant="info" />
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h2 class="card-title mb-0">Your tokens</h2>

            @if ($tokens->isNotEmpty())
                <form method="POST" action="{{ route('admin.system.tokens.destroy-others') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            data-confirm="Revoke every token except the one you are using?">
                        Revoke all others
                    </button>
                </form>
            @endif
        </div>

        @if ($tokens->isEmpty())
            <div class="card-body">
                <x-empty-state
                    icon="key"
                    title="No API tokens"
                    description="Tokens are issued over the API. Until one exists, integrations must authenticate some other way.">
                    <code class="small text-body-secondary">POST {{ url('/api/v1/tokens') }}</code>
                </x-empty-state>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <caption class="visually-hidden">API tokens belonging to your account</caption>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Abilities</th>
                            <th scope="col">Last used</th>
                            <th scope="col">Expires</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tokens as $token)
                            @php($isCurrent = $token->id === request()->user()->currentAccessToken()?->getKey())
                            <tr>
                                <th scope="row" class="fw-semibold">
                                    {{ $token->name }}
                                    @if ($isCurrent)
                                        {{-- Saying which token is making THIS request is
                                             the single most useful thing on this screen:
                                             it tells an operator which credential to
                                             keep and which to revoke. --}}
                                        <x-badge variant="success" class="ms-1">This session</x-badge>
                                    @endif
                                </th>

                                <td>
                                    @forelse ($token->abilities ?? [] as $ability)
                                        <x-badge variant="secondary" class="me-1 mb-1">{{ $ability }}</x-badge>
                                    @empty
                                        <span class="text-body-secondary small">—</span>
                                    @endforelse
                                </td>

                                <td class="small">
                                    @if ($token->last_used_at)
                                        <time datetime="{{ $token->last_used_at->toIso8601String() }}">
                                            {{ $token->last_used_at->diffForHumans() }}
                                        </time>
                                    @else
                                        {{-- "Never" is information. An unused token is a
                                             credential that exists only as risk. --}}
                                        <span class="text-body-secondary">Never used</span>
                                    @endif
                                </td>

                                <td class="small">
                                    @if ($token->expires_at)
                                        <time datetime="{{ $token->expires_at->toIso8601String() }}"
                                              class="{{ $token->expires_at->isPast() ? 'text-danger' : '' }}">
                                            {{ $token->expires_at->toDateString() }}
                                        </time>
                                        @if ($token->expires_at->isPast())
                                            <x-badge variant="danger" class="ms-1">Expired</x-badge>
                                        @endif
                                    @else
                                        {{-- Absent expiry means the deployment has turned
                                             Sanctum's expiration off. Worth saying plainly
                                             rather than showing a blank. --}}
                                        <x-badge variant="warning">No expiry</x-badge>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <form method="POST"
                                          action="{{ route('admin.system.tokens.destroy', $token->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                data-confirm="Revoke this token? Anything using it stops working immediately."
                                                aria-label="Revoke {{ $token->name }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($tokens->hasPages())
                <div class="card-footer">
                    {{ $tokens->links() }}
                </div>
            @endif
        @endif
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h2 class="card-title mb-0">Grantable abilities</h2>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary mb-2">
                A token can only hold these. <code>*</code> — the whole account — is deliberately
                not grantable over HTTP; use the CLI command if it is genuinely needed.
            </p>
            @foreach ($grantedAbilities as $ability)
                <code class="me-1 mb-1">{{ $ability }}</code>
            @endforeach
        </div>
    </div>
@endsection
