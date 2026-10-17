<?php

namespace App\Livewire\Account\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Request a customer password-reset link by email. Never reveals whether an
 * email is registered; the "sent" screen offers a resend with a 60-second
 * cooldown (the broker also throttles at 60s server-side).
 */
#[Layout('components.layouts.auth')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public bool $sent = false;

    /** Seconds remaining before resend is allowed again. */
    public int $cooldown = 0;

    /** Captured at mount so route() works during /livewire/update (setlocale is not persistent). */
    public string $locale = '';

    public function mount(): void
    {
        $this->locale = app()->getLocale();
    }

    public function send(): void
    {
        $this->validate();

        Password::broker('customers')->sendResetLink(['email' => mb_strtolower(trim($this->email))]);

        $this->sent = true;
        $this->cooldown = 60;
    }

    public function resend(): void
    {
        if ($this->cooldown > 0) {
            return;
        }

        Password::broker('customers')->sendResetLink(['email' => mb_strtolower(trim($this->email))]);

        $this->cooldown = 60;
        $this->dispatch('toast', tone: 'success', title: __('account.forgot.resent'));
    }

    /** Masked email for the "check your inbox" screen. */
    public function maskedEmail(): string
    {
        $email = mb_strtolower(trim($this->email));
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($user === '') {
            return $email;
        }

        $tail = mb_strlen($user) > 2 ? mb_substr($user, -1) : '';

        return mb_substr($user, 0, 1).'•••'.$tail.'@'.$domain;
    }

    public function render(): View
    {
        return view('livewire.account.auth.forgot-password');
    }
}
