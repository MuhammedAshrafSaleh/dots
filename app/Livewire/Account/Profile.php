<?php

namespace App\Livewire\Account;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** Edit personal details and change password. */
class Profile extends Component
{
    public string $name = '';

    public string $mobile = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $customer = $this->customer();
        $this->name = $customer->name;
        $this->mobile = $customer->mobile;
        $this->email = $customer->email;
    }

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer;
    }

    public function saveDetails(): void
    {
        $customer = $this->customer();
        $this->mobile = preg_replace('/[\s-]/', '', $this->mobile) ?? $this->mobile;

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/\S+\s+\S+/'],
            'mobile' => ['required', 'string', 'regex:/^01[0125]\d{8}$/', Rule::unique('customers', 'mobile')->ignore($customer->id)],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', Rule::unique('customers', 'email')->ignore($customer->id)],
        ], attributes: [
            'name' => __('account.profile.name'),
            'mobile' => __('account.profile.mobile'),
            'email' => __('account.profile.email'),
        ]);

        $customer->update([
            'name' => trim($data['name']),
            'mobile' => $data['mobile'],
            'email' => mb_strtolower(trim($data['email'])),
        ]);

        session()->flash('account.flash_details', __('account.profile.details_saved'));
    }

    public function updatePassword(): void
    {
        $customer = $this->customer();

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'regex:/\d/', 'different:current_password'],
            'password_confirmation' => ['required', 'same:password'],
        ], attributes: [
            'current_password' => __('account.profile.current_password'),
            'password' => __('account.profile.new_password'),
            'password_confirmation' => __('account.profile.confirm_password'),
        ]);

        if (! Hash::check($this->current_password, $customer->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('account.profile.current_wrong'),
            ]);
        }

        // New remember token invalidates "remember me" sessions on other devices
        // (mirrors the password-reset flow); this session stays signed in.
        $customer->forceFill([
            'password' => Hash::make($this->password),
            'remember_token' => Str::random(60),
        ])->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('account.flash_password', __('account.profile.password_changed'));
    }

    public function render(): View
    {
        $customer = $this->customer();

        $counts = [
            'orders' => $customer->orders()->count(),
            'wishlist' => $customer->wishlist()->count(),
            'addresses' => $customer->addresses()->count(),
        ];

        return view('livewire.account.profile', [
            'counts' => $counts,
            'locale' => app()->getLocale(),
        ])->layout('components.layouts.app', ['title' => __('account.nav.profile')]);
    }
}
