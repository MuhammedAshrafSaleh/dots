<?php

namespace App\Livewire\Account\Auth;

use App\Actions\LinkGuestOrders;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Services\CartManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Create a storefront account (PRD §9): full name (first + last), Egyptian
 * mobile, email, password ≥ 8 chars incl. a number, accept terms. Logs the new
 * customer in and returns them to checkout when they came from there. Any guest
 * orders that match both mobile and email are attached, and a guest session
 * cart is folded into the new account (PRD §9).
 */
#[Layout('components.layouts.auth', ['asideImage' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1400&q=65'])]
class Register extends Component
{
    public string $name = '';

    public string $mobile = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    #[Url]
    public string $next = '';

    /** Captured at mount so route() works during /livewire/update (setlocale is not persistent). */
    public string $locale = '';

    public function mount(): void
    {
        $this->locale = app()->getLocale();
    }

    public function register(LinkGuestOrders $linkGuestOrders, CartManager $cart): void
    {
        $this->mobile = preg_replace('/[\s-]/', '', $this->mobile) ?? $this->mobile;

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^01[0125]\d{8}$/', Rule::unique('customers', 'mobile')],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', Rule::unique('customers', 'email')],
            'password' => ['required', 'string', 'min:8', 'regex:/\d/', 'confirmed'],
            'terms' => ['accepted'],
        ], attributes: [
            'name' => __('account.register.name'),
            'mobile' => __('account.register.mobile'),
            'email' => __('account.register.email'),
            'password' => __('account.register.password'),
        ]);

        $customer = Customer::create([
            'name' => trim($data['name']),
            'mobile' => $data['mobile'],
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'status' => CustomerStatus::Active,
        ]);

        $customer->forceFill(['last_login_at' => now()])->saveQuietly();

        // Captured before login, which migrates the session id the guest cart
        // is keyed to.
        $guestSessionId = session()->getId();

        Auth::guard('customer')->login($customer);

        $linkGuestOrders->handle($customer);
        $cart->mergeGuestCartOnLogin($guestSessionId);

        session()->regenerate();

        $this->redirectRoute($this->fromCheckout() ? 'checkout' : 'home', $this->locale, navigate: false);
    }

    public function fromCheckout(): bool
    {
        return $this->next === 'checkout';
    }

    public function render(): View
    {
        return view('livewire.account.auth.register');
    }
}
