<?php

use App\Enums\CouponType;
use App\Livewire\Admin\Coupons\Index;
use App\Models\Coupon;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

it('renders the coupons screen', function () {
    Coupon::create(['code' => 'SEEN10', 'type' => CouponType::Percent, 'value' => 10]);

    $this->get(route('admin.coupons'))
        ->assertOk()
        ->assertSee('SEEN10');
});

it('creates a coupon', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('code', 'save20')
        ->set('type', 'percent')
        ->set('value', '20')
        ->set('min_order_total', '500')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showDrawer', false);

    $coupon = Coupon::firstWhere('code', 'SAVE20');
    expect($coupon)->not->toBeNull()
        ->and($coupon->type)->toBe(CouponType::Percent)
        ->and((float) $coupon->value)->toBe(20.0);
});

it('rejects a percentage over 100', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('code', 'BIG')
        ->set('type', 'percent')
        ->set('value', '150')
        ->set('min_order_total', '0')
        ->call('save')
        ->assertHasErrors('value');

    expect(Coupon::where('code', 'BIG')->exists())->toBeFalse();
});

it('toggles active state', function () {
    $coupon = Coupon::create(['code' => 'TOG', 'type' => CouponType::Fixed, 'value' => 50, 'is_active' => true]);

    Livewire::test(Index::class)->call('toggleActive', $coupon->id);

    expect($coupon->fresh()->is_active)->toBeFalse();
});

it('deletes a coupon', function () {
    $coupon = Coupon::create(['code' => 'DEL', 'type' => CouponType::Fixed, 'value' => 50]);

    Livewire::test(Index::class)->call('delete', $coupon->id);

    expect(Coupon::find($coupon->id))->toBeNull();
});

it('forbids create without the permission', function () {
    loginAdmin(['coupons.view']);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertForbidden();
});
