<?php

use App\Models\PageSection;

it('exposes a schema for every documented page.key', function () {
    expect(PageSection::schemaFor('home', 'hero'))->toHaveKey('headline')
        ->and(PageSection::schemaFor('global', 'quote_band'))->toHaveKey('bullets')
        ->and(PageSection::schemaFor('home', 'nope'))->toBe([]);
});

it('resolves translatable scalar fields for the active locale with a fallback', function () {
    $section = new PageSection([
        'page' => 'home',
        'key' => 'hero',
        'content' => [
            'headline' => ['ar' => 'عنوان', 'en' => 'Headline'],
            'button1_link' => '/shop',
        ],
    ]);

    app()->setLocale('ar');
    expect($section->field('headline'))->toBe('عنوان');

    app()->setLocale('en');
    expect($section->field('headline'))->toBe('Headline')
        ->and($section->field('button1_link'))->toBe('/shop') // link = plain string
        ->and($section->field('subheadline'))->toBe('');       // absent → default
});

it('resolves list fields into arrays of typed item maps', function () {
    $section = new PageSection([
        'page' => 'home',
        'key' => 'features',
        'content' => [
            'items' => [
                ['icon' => 'truck', 'text' => ['ar' => 'شحن', 'en' => 'Shipping']],
                ['icon' => 'shield', 'text' => ['ar' => 'ضمان', 'en' => 'Warranty']],
            ],
        ],
    ]);

    app()->setLocale('en');
    $items = $section->field('items');

    expect($items)->toHaveCount(2)
        ->and($items[0])->toBe(['icon' => 'truck', 'text' => 'Shipping'])
        ->and($items[1]['text'])->toBe('Warranty');
});

it('returns null for media fields and unknown fields', function () {
    $section = new PageSection(['page' => 'home', 'key' => 'hero', 'content' => []]);

    expect($section->field('video'))->toBeNull()
        ->and($section->field('does_not_exist'))->toBeNull();
});

it('validates a content payload against the schema', function () {
    expect(PageSection::validateContent('home', 'hero', [
        'headline' => ['ar' => 'x', 'en' => 'y'],
    ]))->toBe([]);

    expect(PageSection::validateContent('home', 'hero', [
        'ghost' => 'x',
    ]))->toContain("Unknown field 'ghost'");

    expect(PageSection::validateContent('home', 'features', [
        'items' => [1, 2, 3, 4, 5],
    ]))->toContain("Field 'items' allows at most 4 items");

    expect(PageSection::validateContent('nope', 'nope', []))->toContain('Unknown section nope.nope');
});
