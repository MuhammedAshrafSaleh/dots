<?php

namespace App\Livewire\Account;

use App\Actions\LinkGuestOrders;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CartManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * Confirmation-screen card (Orders.jsx: KOSaveAccount). A guest who just placed
 * an order with an email can turn it into an account by choosing a password —
 * name, mobile and email come from the order. Creating the account links every
 * matching guest order (mobile AND email) and logs them in. Hidden once an
 * account already exists for that email/mobile (they should log in instead).
 */
class SaveOrderAccount extends Component
{
    public Order $order;

    public string $password = '';

    /** Captured at mount so route() works during /livewire/update. */
    public string $locale = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->locale = app()->getLocale();
    }

    /** Only offer the card to guests whose order carries a not-yet-registered email + mobile. */
    public function offerable(): bool
    {
        if (auth('customer')->check()) {
            return false;
        }

        $email = mb_strtolower(trim((string) $this->order->contact_email));
        $mobile = (string) $this->order->contact_mobile;

        if ($email === '' || $mobile === '' || $this->order->customer_id !== null) {
            return false;
        }

        return ! Customer::where('mobile', $mobile)
            ->orWhereRaw('LOWER(email) = ?', [$email])
            ->exists();
    }

    public function save(LinkGuestOrders $linkGuestOrders, CartManager $cart): void
    {
        if (! $this->offerable()) {
            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'regex:/\d/'],
        ], attributes: [
            'password' => __('account.register.password'),
        ]);

        $customer = Customer::create([
            'name' => trim((string) $this->order->contact_name),
            'mobile' => (string) $this->order->contact_mobile,
            'email' => mb_strtolower(trim((string) $this->order->contact_email)),
            'password' => Hash::make($this->password),
            'status' => CustomerStatus::Active,
        ]);

        $customer->forceFill(['last_login_at' => now()])->saveQuietly();

        $guestSessionId = session()->getId();

        Auth::guard('customer')->login($customer);

        $linkGuestOrders->handle($customer);
        $cart->mergeGuestCartOnLogin($guestSessionId);

        session()->regenerate();

        session()->flash('account.flash', __('account.save_order.created'));

        $this->redirectRoute('account.order', [$this->locale, $this->order->number], navigate: false);
    }

    public function render(): View
    {
        return view('livewire.account.save-order-account');
    }
}
