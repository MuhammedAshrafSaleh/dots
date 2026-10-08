<?php

use App\Services\Settings;
use App\Services\WhatsApp;

beforeEach(function () {
    $this->settings = app(Settings::class);
    $this->wa = app(WhatsApp::class);
});

it('renders a template from settings, replacing placeholders per locale', function () {
    $this->settings->set('whatsapp.tpl_product', [
        'en' => 'Interested in {product_name} ({sku})',
        'ar' => 'مهتم بـ {product_name} ({sku})',
    ]);

    app()->setLocale('en');
    expect($this->wa->message('product', ['product_name' => 'Arc Pendant', 'sku' => 'PH-ARC']))
        ->toBe('Interested in Arc Pendant (PH-ARC)');

    app()->setLocale('ar');
    expect($this->wa->message('product', ['product_name' => 'Arc Pendant', 'sku' => 'PH-ARC']))
        ->toBe('مهتم بـ Arc Pendant (PH-ARC)');
});

it('changes the generated wa.me text when the template setting changes', function () {
    $this->settings->set('contact.whatsapp_number', '201000000000');
    $this->settings->set('whatsapp.tpl_general', ['en' => 'Old message', 'ar' => 'رسالة قديمة']);

    app()->setLocale('en');
    $before = $this->wa->link('general');

    expect($before)->toContain('https://wa.me/201000000000?text=')
        ->and($before)->toContain(rawurlencode('Old message'));

    $this->settings->set('whatsapp.tpl_general', ['en' => 'New message', 'ar' => 'رسالة جديدة']);
    $after = $this->wa->link('general');

    expect($after)->not->toBe($before)
        ->and($after)->toContain(rawurlencode('New message'))
        ->and($after)->not->toContain(rawurlencode('Old message'));
});

it('strips non-digits from the configured number in the wa.me link', function () {
    $this->settings->set('contact.whatsapp_number', '+20 100 000 0000');
    $this->settings->set('whatsapp.tpl_general', ['en' => 'Hi', 'ar' => 'أهلاً']);

    app()->setLocale('en');
    expect($this->wa->link('general'))->toStartWith('https://wa.me/201000000000?text=');
});
