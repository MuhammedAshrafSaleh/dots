<?php

namespace App\Livewire\Admin\Payments;

use App\Enums\PaymentMethodCode;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentGateway;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public bool $showDrawer = false;

    public ?int $editingId = null;

    public string $editingCode = '';

    /** @var array<string, string> */
    public array $name = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $instructions = ['ar' => '', 'en' => ''];

    public int $sort_order = 0;

    public bool $is_active = true;

    // InstaPay settings (shown only for the instapay method).
    public string $instapay_address = '';

    public string $instapay_hold_hours = '24';

    // Card payment window in minutes (shown only for the card method).
    public string $card_payment_window_minutes = '60';

    public function openEdit(int $id, Settings $settings): void
    {
        $this->authorize('payment_methods.update');
        $method = PaymentMethod::findOrFail($id);

        $this->editingId = $method->id;
        $this->editingCode = $method->code->value;
        foreach (['name', 'description', 'instructions'] as $f) {
            $this->{$f} = ['ar' => $method->getTranslation($f, 'ar', false), 'en' => $method->getTranslation($f, 'en', false)];
        }
        $this->sort_order = (int) $method->sort_order;
        $this->is_active = (bool) $method->is_active;

        $this->instapay_address = (string) ($settings->get('commerce.instapay_address') ?? '');
        $this->instapay_hold_hours = (string) ($settings->get('commerce.instapay_hold_hours') ?? '24');
        $this->card_payment_window_minutes = (string) ($settings->get('commerce.card_payment_window_minutes') ?? '60');

        $this->resetErrorBag();
        $this->showDrawer = true;
    }

    public function save(Settings $settings, PaymentGateway $gateway): void
    {
        $this->authorize('payment_methods.update');

        $data = $this->validate([
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'description.en' => ['nullable', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string', 'max:255'],
            'instructions.en' => ['nullable', 'string', 'max:1000'],
            'instructions.ar' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['integer', 'min:0'],
            'instapay_address' => ['nullable', 'string', 'max:255'],
            'instapay_hold_hours' => ['required', 'integer', 'min:1'],
            'card_payment_window_minutes' => ['required', 'integer', 'min:30'],
        ]);

        $method = PaymentMethod::findOrFail($this->editingId);

        // Card can only go active when Paymob is configured on the server.
        $active = $this->is_active;
        if ($method->code === PaymentMethodCode::Card && $active && ! $gateway->isConfigured()) {
            $active = false;
            $this->dispatch('toast', tone: 'warning', title: __('admin.payments.cannot_enable_card'), message: __('admin.payments.cannot_enable_card_body'));
        }

        $method->update([
            'name' => $this->name,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'sort_order' => $data['sort_order'],
            'is_active' => $active,
        ]);

        if ($method->code === PaymentMethodCode::InstapayWhatsapp) {
            $settings->set('commerce.instapay_address', $this->instapay_address);
            $settings->set('commerce.instapay_hold_hours', (int) $this->instapay_hold_hours);
        }

        if ($method->code === PaymentMethodCode::Card) {
            $settings->set('commerce.card_payment_window_minutes', (int) $data['card_payment_window_minutes']);
        }

        $this->is_active = $active;
        $this->showDrawer = false;
        $this->dispatch('toast', tone: 'success', title: __('admin.payments.saved'));
    }

    public function toggleActive(int $id, PaymentGateway $gateway): void
    {
        $this->authorize('payment_methods.update');
        $method = PaymentMethod::findOrFail($id);

        if ($method->code === PaymentMethodCode::Card && ! $method->is_active && ! $gateway->isConfigured()) {
            $this->dispatch('toast', tone: 'warning', title: __('admin.payments.cannot_enable_card'), message: __('admin.payments.cannot_enable_card_body'));

            return;
        }

        $method->update(['is_active' => ! $method->is_active]);
    }

    /**
     * Drag reorder. $ids is the new order of method ids.
     *
     * @param  array<int, int|string>  $ids
     */
    public function reorder(array $ids): void
    {
        $this->authorize('payment_methods.update');
        foreach (array_values($ids) as $position => $id) {
            PaymentMethod::whereKey((int) $id)->update(['sort_order' => $position]);
        }
    }

    public function render(PaymentGateway $gateway): View
    {
        return view('livewire.admin.payments.index', [
            'rows' => PaymentMethod::query()->orderBy('sort_order')->get(),
            'paymobConfigured' => $gateway->isConfigured(),
            'webhookUrl' => route('paymob.webhook'),
        ]);
    }
}
