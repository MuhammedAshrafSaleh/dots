<?php

namespace App\Livewire\Account\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Set a new password from an emailed, single-use link (30-minute validity).
 * Rotating remember_token logs the customer out on every other device
 * (PRD §9).
 */
#[Layout('components.layouts.auth', ['asideImage' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1400&q=65'])]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string|min:8|regex:/\d/|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public bool $done = false;

    /** Set to true on mount when the token is missing, expired, or already used. */
    public bool $invalid = false;

    /** Captured at mount so route() works during /livewire/update (setlocale is not persistent). */
    public string $locale = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
        $this->locale = app()->getLocale();

        $this->invalid = ! $this->tokenIsUsable();
    }

    /** Check token existence and expiry without consuming it. */
    private function tokenIsUsable(): bool
    {
        $email = mb_strtolower(trim($this->email));

        if ($email === '') {
            return false;
        }

        $record = DB::table('customer_password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return false;
        }

        if (! Hash::check($this->token, $record->token)) {
            return false;
        }

        $expireMinutes = (int) config('auth.passwords.customers.expire', 30);

        return now()->diffInMinutes($record->created_at, true) < $expireMinutes;
    }

    public function save(): void
    {
        $key = 'reset-password.'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('ui.too_many_attempts'),
            ]);
        }
        RateLimiter::hit($key, 600);

        $this->validate(attributes: ['password' => __('account.register.password')]);

        $status = Password::broker('customers')->reset([
            'email' => mb_strtolower(trim($this->email)),
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'token' => $this->token,
        ], function ($customer) {
            $customer->forceFill([
                'password' => Hash::make($this->password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($customer));
        });

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $this->done = true;
    }

    public function render(): View
    {
        return view('livewire.account.auth.reset-password');
    }
}
