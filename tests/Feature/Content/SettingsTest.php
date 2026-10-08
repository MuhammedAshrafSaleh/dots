<?php

use App\Models\Setting;
use App\Services\Settings;

beforeEach(function () {
    $this->settings = app(Settings::class);
});

it('returns the default when a key is unset', function () {
    expect($this->settings->get('commerce.order_prefix', 'DT-'))->toBe('DT-');
});

it('stores and reads scalar and translatable values', function () {
    $this->settings->set('commerce.order_prefix', 'DT-');
    $this->settings->set('contact.showroom_label', ['ar' => 'المعرض', 'en' => 'Showroom']);

    expect($this->settings->get('commerce.order_prefix'))->toBe('DT-')
        ->and($this->settings->get('contact.showroom_label'))->toBe(['ar' => 'المعرض', 'en' => 'Showroom']);
});

it('busts the cache on write', function () {
    $this->settings->set('seo.default_title', 'Old');
    expect($this->settings->get('seo.default_title'))->toBe('Old'); // caches

    $this->settings->set('seo.default_title', 'New');
    expect($this->settings->get('seo.default_title'))->toBe('New');
});

it('does not hit the database again once cached', function () {
    $this->settings->set('footer.copyright', '2026');
    $this->settings->get('footer.copyright'); // warms cache

    Setting::query()->where('group', 'footer')->where('key', 'copyright')->delete();

    // Still served from cache because set() only forgets its own key.
    expect($this->settings->get('footer.copyright'))->toBe('2026');
});

it('resolves translatable settings for a locale with fallback', function () {
    $this->settings->set('contact.showroom_label', ['ar' => 'المعرض', 'en' => 'Showroom']);

    expect($this->settings->translated('contact.showroom_label', 'ar'))->toBe('المعرض')
        ->and($this->settings->translated('contact.showroom_label', 'en'))->toBe('Showroom');
});
