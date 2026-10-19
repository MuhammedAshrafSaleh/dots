<?php

namespace App\Livewire\Admin\Customers;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Services\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Detail extends Component
{
    public Customer $customer;

    public string $adminNotes = '';

    public function mount(Customer $customer): void
    {
        $this->authorize('customers.view');

        $this->customer = $customer;
        $this->adminNotes = (string) $customer->admin_notes;
    }

    public function saveNotes(): void
    {
        $this->authorize('customers.update');

        $this->validate(['adminNotes' => ['nullable', 'string', 'max:2000']]);

        $this->customer->admin_notes = trim($this->adminNotes) === '' ? null : $this->adminNotes;
        $this->customer->save();

        $this->dispatch('toast', tone: 'success', title: __('admin.customers.notes_saved'));
    }

    /**
     * Flip active/disabled. Disabling rotates the remember token so the
     * remember-me cookie can't re-authenticate; the `customer.active`
     * middleware ends the live session on the customer's next request.
     */
    public function toggleStatus(): void
    {
        $this->authorize('customers.update');

        $disabling = $this->customer->status === CustomerStatus::Active;

        $this->customer->status = $disabling ? CustomerStatus::Disabled : CustomerStatus::Active;

        if ($disabling) {
            $this->customer->setRememberToken(Str::random(60));
        }

        $this->customer->save();

        $this->dispatch(
            'toast',
            tone: $disabling ? 'warning' : 'success',
            title: $disabling ? __('admin.customers.disabled') : __('admin.customers.enabled'),
            message: $disabling ? __('admin.customers.disabled_hint') : '',
        );
    }

    private function whatsappUrl(): string
    {
        return 'https://wa.me/'.WhatsApp::normalizeNumber($this->customer->mobile);
    }

    public function render(): View
    {
        $this->customer->loadMissing([
            'addresses.governorate',
            'wishlist.brand',
        ]);

        $orders = $this->customer->orders()
            ->withCount('items as items_count')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();

        $spent = (float) $orders->sum(fn ($o) => (float) $o->total);
        $count = $orders->count();

        return view('livewire.admin.customers.detail', [
            'customer' => $this->customer,
            'orders' => $orders,
            'stats' => [
                'orders' => $count,
                'spent' => $spent,
                'average' => $count > 0 ? $spent / $count : 0.0,
                'wishlist' => $this->customer->wishlist->count(),
            ],
            'whatsappUrl' => $this->whatsappUrl(),
            'locale' => app()->getLocale(),
        ]);
    }
}
