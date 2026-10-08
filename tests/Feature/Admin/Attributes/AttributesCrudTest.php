<?php

use App\Livewire\Admin\Attributes\Edit;
use App\Livewire\Admin\Attributes\Index;
use App\Models\Attribute;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

it('gates the list behind the attributes.view permission', function () {
    loginAdmin(['dashboard.view']); // no attributes.view
    $this->get(route('admin.attributes'))->assertForbidden();

    loginAdmin(['attributes.view']);
    $this->get(route('admin.attributes'))->assertOk();
});

it('lists attributes and filters by search and display type', function () {
    Attribute::create(['code' => 'cct', 'name' => ['ar' => 'حرارة', 'en' => 'Colour temperature'], 'display' => 'chip']);
    Attribute::create(['code' => 'finish', 'name' => ['ar' => 'تشطيب', 'en' => 'Finish'], 'display' => 'swatch']);

    Livewire::test(Index::class)
        ->assertSee('cct')
        ->assertSee('finish')
        ->set('search', 'finish')
        ->assertSee('finish')
        ->assertDontSee('cct')
        ->set('search', '')
        ->set('displayType', 'swatch')
        ->assertSee('finish')
        ->assertDontSee('cct');
});

it('creates an attribute with a value, lowercasing the code', function () {
    $component = Livewire::test(Edit::class)
        ->set('code', 'CCT')
        ->set('unit', 'K')
        ->set('name.en', 'Colour temperature')
        ->set('name.ar', 'درجة حرارة اللون')
        ->set('display', 'chip')
        ->set('is_variant_axis', true)
        ->call('addValue');

    $key = array_key_first($component->get('values'));
    $component->set("values.$key.value", '2700')
        ->set("values.$key.label.en", '2700K')
        ->set("values.$key.label.ar", '2700 كلفن')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.attributes'));

    $attr = Attribute::where('code', 'cct')->firstOrFail();
    expect($attr->getTranslation('name', 'en'))->toBe('Colour temperature')
        ->and($attr->unit)->toBe('K')
        ->and($attr->is_variant_axis)->toBeTrue()
        ->and($attr->values()->count())->toBe(1)
        ->and($attr->values()->first()->value)->toBe('2700');
});

it('lowercases the code and requires it to be unique', function () {
    Attribute::create(['code' => 'size', 'name' => ['ar' => 'مقاس', 'en' => 'Size'], 'display' => 'button']);

    Livewire::test(Edit::class)
        ->set('code', 'SIZE')
        ->set('name.en', 'Size again')
        ->set('name.ar', 'مقاس')
        ->call('save')
        ->assertHasErrors('code');
});

it('reports validation errors on empty value rows', function () {
    $component = Livewire::test(Edit::class)
        ->set('code', 'beam')
        ->set('name.en', 'Beam')
        ->set('name.ar', 'شعاع')
        ->call('addValue');

    $key = array_key_first($component->get('values'));

    $component->call('save')
        ->assertHasErrors(["values.$key.value", "values.$key.label.en"]);
});

it('edits an existing attribute and syncs values', function () {
    $attr = Attribute::create(['code' => 'finish', 'name' => ['ar' => 'تشطيب', 'en' => 'Finish'], 'display' => 'swatch']);
    $v1 = $attr->values()->create(['value' => 'black', 'label' => ['ar' => 'أسود', 'en' => 'Black'], 'sort_order' => 0]);
    $attr->values()->create(['value' => 'white', 'label' => ['ar' => 'أبيض', 'en' => 'White'], 'sort_order' => 1]);

    $component = Livewire::test(Edit::class, ['attribute' => $attr]);

    // Remove the second value, keep the first.
    $keys = array_keys($component->get('values'));
    $component->call('removeValue', $keys[1])
        ->set("values.{$keys[0]}.label.en", 'Matte black')
        ->call('save')
        ->assertHasNoErrors();

    $attr->refresh();
    expect($attr->values()->count())->toBe(1)
        ->and($attr->values()->first()->id)->toBe($v1->id)
        ->and($attr->values()->first()->getTranslation('label', 'en'))->toBe('Matte black');
});

it('deletes and duplicates from the list', function () {
    $attr = Attribute::create(['code' => 'watt', 'name' => ['ar' => 'قدرة', 'en' => 'Wattage'], 'display' => 'button']);
    $attr->values()->create(['value' => '12', 'label' => ['ar' => '12', 'en' => '12 W'], 'sort_order' => 0]);

    Livewire::test(Index::class)
        ->call('duplicate', $attr->id)
        ->call('delete', $attr->id);

    expect(Attribute::where('code', 'watt')->exists())->toBeFalse()
        ->and(Attribute::where('code', 'watt-copy')->exists())->toBeTrue()
        ->and(Attribute::where('code', 'watt-copy')->first()->values()->count())->toBe(1);
});

it('toggles filterable and variant flags inline', function () {
    $attr = Attribute::create(['code' => 'ip', 'name' => ['ar' => 'حماية', 'en' => 'IP'], 'display' => 'chip', 'is_filterable' => true, 'is_variant_axis' => false]);

    Livewire::test(Index::class)
        ->call('toggleFilterable', $attr->id)
        ->call('toggleVariants', $attr->id);

    $attr->refresh();
    expect($attr->is_filterable)->toBeFalse()
        ->and($attr->is_variant_axis)->toBeTrue();
});
