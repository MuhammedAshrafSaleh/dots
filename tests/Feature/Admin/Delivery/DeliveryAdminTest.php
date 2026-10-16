<?php

use App\Livewire\Admin\Delivery\Index;
use App\Models\DeliveryMethod;
use App\Models\Governorate;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function makeGov(string $en): Governorate
{
    return Governorate::create(['name' => ['ar' => $en, 'en' => $en], 'sort_order' => 0, 'is_active' => true]);
}

it('renders the delivery screen', function () {
    DeliveryMethod::create(['name' => ['ar' => 'قياسي', 'en' => 'Standard delivery'], 'price' => 150]);

    $this->get(route('admin.delivery'))->assertOk()->assertSee('قياسي');
});

it('creates a delivery method with governorates', function () {
    $cairo = makeGov('Cairo');
    $giza = makeGov('Giza');

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name.en', 'Express')
        ->set('name.ar', 'سريع')
        ->set('price', '300')
        ->call('toggleGov', $cairo->id)
        ->call('toggleGov', $giza->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showDrawer', false);

    $method = DeliveryMethod::where('name->en', 'Express')->first();
    expect($method)->not->toBeNull()
        ->and((float) $method->price)->toBe(300.0)
        ->and($method->governorates()->count())->toBe(2);
});

it('requires a bilingual name', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name.en', '')
        ->set('name.ar', '')
        ->call('save')
        ->assertHasErrors(['name.en', 'name.ar']);
});

it('reorders methods by the given id order', function () {
    $a = DeliveryMethod::create(['name' => ['ar' => 'أ', 'en' => 'A'], 'sort_order' => 0]);
    $b = DeliveryMethod::create(['name' => ['ar' => 'ب', 'en' => 'B'], 'sort_order' => 1]);

    Livewire::test(Index::class)->call('reorder', [$b->id, $a->id]);

    expect($b->fresh()->sort_order)->toBe(0)
        ->and($a->fresh()->sort_order)->toBe(1);
});

it('toggles active state', function () {
    $method = DeliveryMethod::create(['name' => ['ar' => 'x', 'en' => 'X'], 'is_active' => true]);

    Livewire::test(Index::class)->call('toggleActive', $method->id);

    expect($method->fresh()->is_active)->toBeFalse();
});
