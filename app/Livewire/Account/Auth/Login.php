<?php

namespace App\Livewire\Account\Auth;

use App\Enums\CustomerStatus;
use App\Support\Auth\LoginThrottle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Storefront login: mobile OR email + password, remember me, 5-attempts /
 * 15-minute lockout. Disabled customers can never log in (PRD §9). When the
 * visitor arrives from checkout (?next=checkout) a "continue as guest" path is
 * offered and a successful login returns them to checkout.
 */
#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string')]
    public string $identifier = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = true;

    #[Url]
    public string $next = '';

    /** Captured at mount so route() works during /livewire/update (setlocale is not persistent). */
    public string $locale = '';

    /** Attempts remaining before the lock; shown in the error alert. */
    public ?int $attemptsLeft = null;

    public function mount(): void
    {
        $this->locale = app()->getLocale();
    }

    public function login(): void
    {
        $this->validate();

        [$field, $value] = $this->resolveCredential();

        $throttle = LoginThrottle::for('customer', $value, request()->ip());
        $throttle->ensureNotLocked('identifier');

        $ok = Auth::guard('customer')->attempt([
            $field => $value,
            'password' => $this->password,
            'status' => CustomerStatus::Active->value,
        ], $this->remember);

        if (! $ok) {
            $throttle->hit();
            $this->attemptsLeft = $throttle->remaining();
            $this->reset('password');

            throw ValidationException::withMessages([
                'identifier' => __('account.login.failed'),
            ]);
        }

        $throttle->clear();

        $customer = Auth::guard('customer')->user();
        $customer?->forceFill(['last_login_at' => now()])->saveQuietly();

        session()->regenerate();

        $this->redirectRoute($this->fromCheckout() ? 'checkout' : 'home', $this->locale, navigate: false);
    }

    /** Whether this screen was opened from checkout. */
    public function fromCheckout(): bool
    {
        return $this->next === 'checkout';
    }

    /**
     * Decide whether the identifier is an email or an Egyptian mobile and
     * normalise it. Rejects anything that is neither.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveCredential(): array
    {
        $raw = trim($this->identifier);

        if (str_contains($raw, '@')) {
            return ['email', $raw];
        }

        $mobile = preg_replace('/[\s-]/', '', $raw) ?? $raw;

        if (preg_match('/^01[0125]\d{8}$/', $mobile) !== 1) {
            throw ValidationException::withMessages([
                'identifier' => __('account.login.identifier_invalid'),
            ]);
        }

        return ['mobile', $mobile];
    }

    public function render(): View
    {
        return view('livewire.account.auth.login');
    }
}
