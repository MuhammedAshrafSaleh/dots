<?php

namespace App\Livewire\Account;

use App\Enums\AddressLabel;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Governorate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Saved delivery addresses: add/edit in a drawer, set a default, delete with a
 * confirm step. Exactly one address is the default at any time.
 */
class Addresses extends Component
{
    /** null = drawer closed, 0 = adding new, >0 = editing that id. */
    #[Locked]
    public ?int $editingId = null;

    /** Address pending deletion (shows the confirm dialog). */
    #[Locked]
    public ?int $deletingId = null;

    // Drawer form fields.
    public string $label = 'home';

    public string $recipient_name = '';

    public string $mobile = '';

    public ?int $governorate_id = null;

    public string $city = '';

    public string $street = '';

    public string $floor_apartment = '';

    public string $landmark = '';

    public bool $is_default = false;

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer;
    }

    /** @return Collection<int, CustomerAddress> */
    private function addresses(): Collection
    {
        return $this->customer()->addresses()
            ->with('governorate')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }

    public function create(): void
    {
        if ($this->customer()->addresses()->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            throw ValidationException::withMessages([
                '_limit' => __('account.addresses.limit_reached', ['max' => CustomerAddress::MAX_PER_CUSTOMER]),
            ]);
        }

        $this->resetForm();
        $this->is_default = $this->customer()->addresses()->count() === 0;
        $this->editingId = 0;
    }

    public function edit(int $id): void
    {
        $address = $this->customer()->addresses()->findOrFail($id);

        $this->editingId = $address->id;
        $this->label = $address->label->value;
        $this->recipient_name = $address->recipient_name;
        $this->mobile = $address->mobile;
        $this->governorate_id = $address->governorate_id;
        $this->city = $address->city;
        $this->street = $address->street;
        $this->floor_apartment = (string) $address->floor_apartment;
        $this->landmark = (string) $address->landmark;
        $this->is_default = $address->is_default;
    }

    public function closeDrawer(): void
    {
        $this->editingId = null;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->mobile = preg_replace('/[\s-]/', '', $this->mobile) ?? $this->mobile;

        $data = $this->validate([
            'label' => ['required', Rule::enum(AddressLabel::class)],
            'recipient_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^01[0125]\d{8}$/'],
            'governorate_id' => ['required', Rule::exists('governorates', 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'floor_apartment' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ], attributes: [
            'recipient_name' => __('account.addresses.recipient'),
            'mobile' => __('account.addresses.mobile'),
            'governorate_id' => __('account.addresses.governorate'),
            'city' => __('account.addresses.city'),
            'street' => __('account.addresses.street'),
            'floor_apartment' => __('account.addresses.floor'),
        ]);

        $customer = $this->customer();
        $isNew = $this->editingId === 0;

        if ($isNew && $customer->addresses()->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            throw ValidationException::withMessages([
                '_limit' => __('account.addresses.limit_reached', ['max' => CustomerAddress::MAX_PER_CUSTOMER]),
            ]);
        }

        if ($isNew) {
            /** @var CustomerAddress $address */
            $address = $customer->addresses()->create($data);
        } else {
            $address = $customer->addresses()->findOrFail($this->editingId);
            $address->update($data);
        }

        $this->normaliseDefault($address);

        session()->flash('account.flash', $isNew ? __('account.addresses.saved') : __('account.addresses.updated'));
        $this->closeDrawer();
    }

    public function makeDefault(int $id): void
    {
        $address = $this->customer()->addresses()->findOrFail($id);
        $address->forceFill(['is_default' => true])->save();
        $this->normaliseDefault($address);
        session()->flash('account.flash', __('account.addresses.default_updated'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $this->customer()->addresses()->findOrFail($id)->id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $address = $this->customer()->addresses()->findOrFail($this->deletingId);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $this->customer()->addresses()->orderBy('id')->first();
            $next?->forceFill(['is_default' => true])->save();
        }

        session()->flash('account.flash', __('account.addresses.deleted'));
        $this->deletingId = null;
    }

    /** Ensure exactly one default: the given address if it is default, else keep one. */
    private function normaliseDefault(CustomerAddress $address): void
    {
        if ($address->is_default) {
            $this->customer()->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);

            return;
        }

        // Nothing is default (e.g. unchecked on the only address) → promote the first.
        if ($this->customer()->addresses()->where('is_default', true)->doesntExist()) {
            $first = $this->customer()->addresses()->orderBy('id')->first();
            $first?->forceFill(['is_default' => true])->save();
        }
    }

    private function resetForm(): void
    {
        $customer = $this->customer();
        $this->reset(['label', 'recipient_name', 'mobile', 'governorate_id', 'city', 'street', 'floor_apartment', 'landmark', 'is_default']);
        $this->resetValidation();
        $this->recipient_name = $customer->name;
        $this->mobile = $customer->mobile;
    }

    public function render(): View
    {
        $addresses = $this->addresses();
        $customer = $this->customer();

        $counts = [
            'orders' => $customer->orders()->count(),
            'wishlist' => $customer->wishlist()->count(),
            'addresses' => $addresses->count(),
        ];

        return view('livewire.account.addresses', [
            'addresses' => $addresses,
            'governorates' => Governorate::active()->orderBy('sort_order')->orderBy('id')->get(),
            'deleting' => $this->deletingId !== null ? $addresses->firstWhere('id', $this->deletingId) : null,
            'counts' => $counts,
            'locale' => app()->getLocale(),
        ])->layout('components.layouts.app', ['title' => __('account.nav.addresses')]);
    }
}
