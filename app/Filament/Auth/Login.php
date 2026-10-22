<?php

namespace App\Filament\Auth;

use App\Models\LoginLog;
use App\Services\LoginLogService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The application's one sign-in page.
 *
 * Filament's login, with what the application's own login had kept on top of it:
 * five attempts a minute per email and address, every outcome written to the login
 * log (signed in, failed, locked), and a refusal for accounts that are not active
 * (Filament refuses those through `canAccessPanel()`).
 */
class Login extends BaseLogin
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function authenticate(): ?LoginResponse
    {
        $email = (string) ($this->data['email'] ?? '');
        $key = $this->throttleKey($email);
        $logs = app(LoginLogService::class);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $logs->recordFailure(request(), $email, LoginLog::LOCKED);

            Notification::make()
                ->title(__('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                    'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
                ]))
                ->danger()
                ->send();

            return null;
        }

        try {
            $response = parent::authenticate();
        } catch (ValidationException $e) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            $logs->recordFailure(
                request(),
                $email,
                RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS) ? LoginLog::LOCKED : LoginLog::FAILED,
            );

            throw $e;
        }

        if ($response !== null) {
            RateLimiter::clear($key);

            $logs->recordLogin(request(), Filament::auth()->user());
        }

        return $response;
    }

    /**
     * Filament's own limiter counts attempts per address, so one person's mistakes
     * would lock out everybody behind the same address. The limiter above is keyed
     * on the email as well, which is the rule this page has always had.
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null): void {}

    protected function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|'.request()->ip());
    }
}
