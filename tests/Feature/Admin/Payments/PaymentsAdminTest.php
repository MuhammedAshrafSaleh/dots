<?php

use App\Enums\PaymentMethodCode;
use App\Livewire\Admin\Payments\Index;
use App\Models\PaymentMethod;
use App\Services\Settings;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function makePaymentMethod(PaymentMethodCode $code, bool $active = false): PaymentMethod
{
    return PaymentMethod::create([
        'code' => $code,
        'name' => ['ar' => $code->value, 'en' => $code->value],
        'is_active' => $active,
        'sort_order' => 0,
    ]);
}

it('renders the payments screen', function () {
    makePaymentMethod(PaymentMethodCode::Cod, true);

    $this->get(route('admin.payments'))->assertOk();
});

it('shows Paymob as not configured without env keys', function () {
    makePaymentMethod(PaymentMethodCode::Card);

    Livewire::test(Index::class)->assertSet('showDrawer', false)
        ->assertViewHas('paymobConfigured', false);
});

it('will not enable card while Paymob is unconfigured', function () {
    $card = makePaymentMethod(PaymentMethodCode::Card, false);

    Livewire::test(Index::class)->call('toggleActive', $card->id);

    expect($card->fresh()->is_active)->toBeFalse();
});

it('saves instapay address and hold hours', function () {
    $instapay = makePaymentMethod(PaymentMethodCode::InstapayWhatsapp, true);

    Livewire::test(Index::class)
        ->call('openEdit', $instapay->id)
        ->set('name.en', 'InstaPay')
        ->set('name.ar', 'إنستاباي')
        ->set('instapay_address', 'dots@instapay')
        ->set('instapay_hold_hours', '48')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showDrawer', false);

    $settings = app(Settings::class);
    $settings->forget('commerce.instapay_address');
    $settings->forget('commerce.instapay_hold_hours');

    expect($settings->get('commerce.instapay_address'))->toBe('dots@instapay')
        ->and((int) $settings->get('commerce.instapay_hold_hours'))->toBe(48);
});

it('updates a payment method name and order', function () {
    $cod = makePaymentMethod(PaymentMethodCode::Cod, true);

    Livewire::test(Index::class)
        ->call('openEdit', $cod->id)
        ->set('name.en', 'Pay cash')
        ->set('name.ar', 'نقدي')
        ->set('sort_order', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect($cod->fresh()->getTranslation('name', 'en', false))->toBe('Pay cash')
        ->and((int) $cod->fresh()->sort_order)->toBe(3);
});
