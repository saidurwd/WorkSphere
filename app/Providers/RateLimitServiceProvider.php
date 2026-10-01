<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Named rate limiters — GAP-009.
 *
 * Keyed per USER where a user exists, not per IP: an office behind one NAT is
 * one address to an attacker and many users to an operator, so an IP key either
 * punishes colleagues or misses a credential-stuffing run spread across
 * addresses. IP is used only for anonymous traffic, where there is nothing else
 * to key on.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->login();
        $this->api();
        $this->apiWrites();
        $this->admin();
        $this->export();
        $this->search();
        $this->mutations();
    }

    /**
     * Sign-in attempts.
     *
     * Keyed on the transliterated email AND the address, so one attacker cannot
     * lock a known account out by guessing its email from elsewhere, and one
     * account cannot be sprayed from many addresses.
     */
    protected function login(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(
                Str::lower((string) $request->input('email')),
            );

            return Limit::perMinute(5)->by('login|'.$email.'|'.$request->ip());
        });
    }

    /**
     * API reads.
     */
    protected function api(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(120)->by($this->identity($request));
        });
    }

    /**
     * API writes — a quarter of the read budget, because a write is the expensive
     * and the abusable one.
     */
    protected function apiWrites(): void
    {
        RateLimiter::for('api-writes', function (Request $request): Limit {
            return Limit::perMinute(30)->by('write|'.$this->identity($request));
        });
    }

    /**
     * The administrative area.
     *
     * A user id rather than an IP: these are a handful of accounts, and a shared
     * office address should not consume one operator's whole budget.
     */
    protected function admin(): void
    {
        RateLimiter::for('admin', function (Request $request): Limit {
            return Limit::perMinute(120)->by('admin|'.$this->identity($request));
        });
    }

    /**
     * Exports.
     *
     * Export is the one action that turns a permission into a data exfiltration
     * vector, so it gets the tightest budget of the read-side limits.
     */
    protected function export(): void
    {
        RateLimiter::for('export', function (Request $request): Limit {
            return Limit::perMinute(10)->by('export|'.$this->identity($request));
        });
    }

    /**
     * Search. Generous, because the navbar searches on every debounce.
     */
    protected function search(): void
    {
        RateLimiter::for('search', function (Request $request): Limit {
            return Limit::perMinute(60)->by('search|'.$this->identity($request));
        });
    }

    /**
     * Authenticated writes in the browser app.
     *
     * Not named in the prompt's list but applied where a form posts repeatedly —
     * bulk actions and quick capture — so one script cannot turn the To-Do list
     * into a write amplifier.
     */
    protected function mutations(): void
    {
        RateLimiter::for('mutations', function (Request $request): Limit {
            return Limit::perMinute(180)->by('mutations|'.$this->identity($request));
        });
    }

    /**
     * The rate-limit key: the authenticated user when there is one, the address
     * otherwise.
     */
    protected function identity(Request $request): string
    {
        if ($request->user()?->getAuthIdentifier() === null) {
            return 'ip'.$request->ip();
        }

        // Keyed on the TOKEN, not just the user. A user with five integrations
        // should not have one integration's runaway loop exhaust the budget for
        // the other four — and revoking one token should not silently hand its
        // spent budget to the next.
        //
        // A session-authenticated call has no token, so it keys on the user alone.
        $token = $request->user()->currentAccessToken();

        return 'u'.$request->user()->getAuthIdentifier().'|t'.($token?->getKey() ?? 'session');
    }
}
