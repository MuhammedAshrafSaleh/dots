<?php

beforeEach(fn () => $this->withoutVite());

it('renders the design primitives for Arabic (rtl)', function () {
    $this->get('/ar/_styleguide')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('d-btn d-btn--primary', false)
        ->assertSee('d-badge d-badge--sale', false)
        ->assertSee('class="d-input"', false);
});

it('renders the design primitives for English (ltr)', function () {
    $this->get('/en/_styleguide')
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee('d-btn d-btn--whatsapp', false);
});

it('renders an x-icon as an inline svg with the d-icon class and 1.5 stroke', function () {
    $html = $this->get('/en/_styleguide')->content();

    expect($html)
        ->toContain('<svg')
        ->toContain('d-icon')
        ->toContain('stroke-width="1.5"');
});

it('flips directional icons in rtl but not in ltr', function () {
    // chevron-right is in the directional set -> mirrored only in Arabic.
    expect($this->get('/ar/_styleguide')->content())->toContain('scaleX(-1)');
    expect($this->get('/en/_styleguide')->content())->not->toContain('scaleX(-1)');
});
