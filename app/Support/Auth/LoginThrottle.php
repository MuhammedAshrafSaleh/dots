<?php

namespace App\Support\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Shared login throttling for both the admin and customer guards.
 * Locks an identifier+IP after 5 failed attempts for 15 minutes.
 *
 * Usage:
 *   $throttle = LoginThrottle::for('admin', $email, $request->ip());
 *   $throttle->ensureNotLocked();         // throws ValidationException when locked
 *   // ... attempt auth ...
 *   $ok ? $throttle->clear() : $throttle->hit();
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 15 * 60;

    public function __construct(private string $key) {}

    public static function for(string $guard, string $identifier, ?string $ip): self
    {
        $key = Str::transliterate(Str::lower(trim($identifier)).'|'.$guard.'|'.($ip ?? ''));

        return new self('login|'.sha1($key));
    }

    public function tooManyAttempts(): bool
    {
        return RateLimiter::tooManyAttempts($this->key, self::MAX_ATTEMPTS);
    }

    /** Record one failed attempt; returns attempts used so far. */
    public function hit(): int
    {
        return RateLimiter::hit($this->key, self::DECAY_SECONDS);
    }

    public function clear(): void
    {
        RateLimiter::clear($this->key);
    }

    public function remaining(): int
    {
        return RateLimiter::remaining($this->key, self::MAX_ATTEMPTS);
    }

    /** Seconds until the lock lifts. */
    public function availableIn(): int
    {
        return RateLimiter::availableIn($this->key);
    }

    /**
     * Throw a localized throttle error on the `email` field when locked.
     * Mirrors Laravel's standard auth throttle message.
     */
    public function ensureNotLocked(string $field = 'email'): void
    {
        if (! $this->tooManyAttempts()) {
            return;
        }

        $seconds = $this->availableIn();

        throw ValidationException::withMessages([
            $field => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }
}
