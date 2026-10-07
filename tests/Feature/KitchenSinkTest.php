<?php

beforeEach(fn () => $this->withoutVite());

it('renders the component kitchen sink in Arabic (rtl)', function () {
    $this->get('/ar/_kitchen-sink')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('d-btn d-btn--primary', false)
        ->assertSee('d-badge d-badge--sale', false)
        ->assertSee('d-chip d-chip--active', false)
        ->assertSee('d-stepper', false)
        ->assertSee('d-tabs', false)
        ->assertSee('d-acc', false)
        ->assertSee('d-crumbs', false)
        ->assertSee('d-pages', false)
        ->assertSee('d-toast d-toast--cart', false);
});

it('renders the kitchen sink in English (ltr)', function () {
    $this->get('/en/_kitchen-sink')
        ->assertOk()
        ->assertSee('dir="ltr"', false);
});

it('localises UI labels from the ui lang file', function () {
    expect($this->get('/ar/_kitchen-sink')->content())->toContain(__('ui.optional', [], 'ar'));
    expect($this->get('/en/_kitchen-sink')->content())->toContain('optional');
});

it('uses the field label for/id association', function () {
    $html = $this->get('/en/_kitchen-sink')->content();
    expect($html)->toMatch('/<label class="d-label" for="field-[A-Za-z0-9]+">/');
});
