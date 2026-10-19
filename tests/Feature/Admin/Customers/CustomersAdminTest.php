<?php

use App\Enums\CustomerStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Livewire\Admin\Customers\Detail;
use App\Livewire\Admin\Customers\Index;
use App\Models\Customer;
use App\Models\Governorate;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function customerOrder(Customer $customer, float $total): Order
{
    $order = Order::create([
        'customer_id' => $customer->id,
        'contact_name' => $customer->name,
        'contact_mobile' => $customer->mobile,
        'contact_email' => $customer->email,
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::Paid,
        'payment_method_code' => 'cod',
        'delivery_method_name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'subtotal' => $total,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => $total,
        'vat_rate' => 14,
        'vat_amount' => round($total * 14 / 114, 2),
        'placed_at' => now(),
    ]);

    $order->items()->create([
        'product_name' => ['ar' => 'منتج', 'en' => 'Item'],
        'sku' => 'SKU-1',
        'unit_price' => $total,
        'qty' => 1,
        'line_total' => $total,
    ]);

    return $order;
}

it('lists customers with order counts and total spent', function () {
    $withOrders = Customer::factory()->create(['name' => 'Mona Adel']);
    customerOrder($withOrders, 1000);
    customerOrder($withOrders, 500);
    $noOrders = Customer::factory()->create(['name' => 'Omar Zaki']);

    Livewire::test(Index::class)
        ->assertSee('Mona Adel')
        ->assertSee('Omar Zaki')
        ->assertSeeInOrder(['Mona Adel', '1,500.00']);
});

it('searches by name, mobile and email', function () {
    Customer::factory()->create(['name' => 'Mona Adel', 'mobile' => '01011112222', 'email' => 'mona@example.com']);
    Customer::factory()->create(['name' => 'Omar Zaki', 'mobile' => '01033334444', 'email' => 'omar@example.com']);

    Livewire::test(Index::class)
        ->set('search', 'Mona')
        ->assertSee('Mona Adel')
        ->assertDontSee('Omar Zaki')
        ->set('search', '01033334444')
        ->assertSee('Omar Zaki')
        ->assertDontSee('Mona Adel')
        ->set('search', 'mona@example.com')
        ->assertSee('Mona Adel')
        ->assertDontSee('Omar Zaki');
});

it('filters by status and by whether they have orders', function () {
    $active = Customer::factory()->create(['name' => 'Active One']);
    customerOrder($active, 200);
    $disabled = Customer::factory()->disabled()->create(['name' => 'Disabled One']);

    Livewire::test(Index::class)
        ->set('status', CustomerStatus::Disabled->value)
        ->assertSee('Disabled One')
        ->assertDontSee('Active One')
        ->set('status', '')
        ->set('orders', 'n')
        ->assertSee('Disabled One')
        ->assertDontSee('Active One')
        ->set('orders', 'y')
        ->assertSee('Active One')
        ->assertDontSee('Disabled One');
});

it('filters by governorate through saved addresses', function () {
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'is_active' => true]);
    $here = Customer::factory()->create(['name' => 'Cairo Customer']);
    $here->addresses()->create([
        'label' => 'home',
        'recipient_name' => $here->name,
        'mobile' => $here->mobile,
        'governorate_id' => $gov->id,
        'city' => 'Maadi',
        'street' => 'St 9',
    ]);
    Customer::factory()->create(['name' => 'No Address']);

    Livewire::test(Index::class)
        ->set('gov', (string) $gov->id)
        ->assertSee('Cairo Customer')
        ->assertDontSee('No Address');
});

it('requires customers.view to open the list', function () {
    loginAdmin(['dashboard.view']);

    $this->get(route('admin.customers'))->assertForbidden();
});

it('shows the customer detail with stats, orders and wishlist', function () {
    $customer = Customer::factory()->create(['name' => 'Mona Adel']);
    customerOrder($customer, 1000);
    customerOrder($customer, 1000);

    Livewire::test(Detail::class, ['customer' => $customer])
        ->assertSee('Mona Adel')
        ->assertSee('2,000.00')   // total spent
        ->assertSee('1,000.00');  // average order
});

it('disables and re-enables an account and rotates the remember token', function () {
    $customer = Customer::factory()->create(['status' => CustomerStatus::Active]);
    $token = $customer->getRememberToken();

    Livewire::test(Detail::class, ['customer' => $customer])
        ->call('toggleStatus');

    $customer->refresh();
    expect($customer->status)->toBe(CustomerStatus::Disabled)
        ->and($customer->getRememberToken())->not->toBe($token);

    Livewire::test(Detail::class, ['customer' => $customer])
        ->call('toggleStatus');

    expect($customer->fresh()->status)->toBe(CustomerStatus::Active);
});

it('saves internal notes', function () {
    $customer = Customer::factory()->create();

    Livewire::test(Detail::class, ['customer' => $customer])
        ->set('adminNotes', 'Prefers WhatsApp over calls.')
        ->call('saveNotes');

    expect($customer->fresh()->admin_notes)->toBe('Prefers WhatsApp over calls.');
});

it('blocks disabling without customers.update', function () {
    loginAdmin(['customers.view']);
    $customer = Customer::factory()->create();

    Livewire::test(Detail::class, ['customer' => $customer])
        ->call('toggleStatus')
        ->assertForbidden();

    expect($customer->fresh()->status)->toBe(CustomerStatus::Active);
});

it('logs a disabled customer out of the account area', function () {
    $customer = Customer::factory()->disabled()->create();

    $this->actingAs($customer, 'customer');

    $this->get(route('account.overview', ['locale' => 'en']))
        ->assertRedirect(route('login', ['locale' => 'en']));

    $this->assertGuest('customer');
});
