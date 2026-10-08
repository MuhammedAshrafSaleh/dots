<?php

use App\Support\Html;

it('keeps the allowed rich-text blocks', function () {
    $html = '<h2>Title</h2><h3>Sub</h3><p><strong>bold</strong> <em>italic</em></p>'
        .'<ul><li>one</li></ul><ol><li>two</li></ol><blockquote>quote</blockquote>'
        .'<a href="https://dots.eg" title="x">link</a>'
        .'<figure><img src="https://dots.eg/a.webp" alt="a"><figcaption>cap</figcaption></figure>'
        .'<div class="d-callout">note</div>';

    $clean = Html::clean($html);

    expect($clean)
        ->toContain('<h2>')
        ->toContain('<h3>')
        ->toContain('<strong>')
        ->toContain('<em>')
        ->toContain('<ul>')
        ->toContain('<ol>')
        ->toContain('<blockquote>')
        ->toContain('href="https://dots.eg"')
        ->toContain('<figure>')
        ->toContain('<figcaption>')
        ->toContain('class="d-callout"');
});

it('strips scripts, event handlers and disallowed tags', function () {
    $html = '<h1>Big</h1><script>alert(1)</script><p onclick="evil()">x</p>'
        .'<table><tr><td>y</td></tr></table><iframe src="https://evil"></iframe>'
        .'<div class="evil">z</div>';

    $clean = Html::clean($html);

    expect($clean)
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('<h1')
        ->not->toContain('<table')
        ->not->toContain('<iframe')
        ->not->toContain('class="evil"');
});

it('returns an empty string for null or blank input', function () {
    expect(Html::clean(null))->toBe('')
        ->and(Html::clean('   '))->toBe('');
});

it('sanitises translatable values', function () {
    $out = Html::cleanTranslations([
        'en' => '<p>ok</p><script>x</script>',
        'ar' => '<h2>عنوان</h2>',
    ]);

    expect($out['en'])->toContain('<p>ok</p>')->not->toContain('<script')
        ->and($out['ar'])->toContain('<h2>عنوان</h2>');
});
