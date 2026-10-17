<?php

namespace App\Livewire\Account\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
#[Layout('components.layouts.auth')]
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

    /** Captured at mount so route() works during /livewire/update (setlocale is not persistent). */
    public string $locale = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
        $this->locale = app()->getLocale();
    }

    public function save(): void
    {
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
