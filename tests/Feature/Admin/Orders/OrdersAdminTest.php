<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Livewire\Admin\Orders\Detail;
use App\Livewire\Admin\Orders\Index;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function adminOrderVariant(string $sku, int $stock = 10): ProductVariant
{
    $brand = Brand::create(['name' => 'B'.$sku, 'slug' => 'b-'.strtolower($sku)]);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'C'],
        'slug' => ['ar' => 'ق-'.$sku, 'en' => 'c-'.strtolower($sku)],
    ]);
    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'منتج '.$sku, 'en' => 'Product '.$sku],
        'slug' => ['ar' => 'م-'.$sku, 'en' => 'p-'.strtolower($sku)],
        'sku' => $sku,
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'min_price' => 1000,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $sku.'-V',
        'price' => 1000,
        'stock_qty' => $stock,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

function adminOrder(array $attributes = [], int $qty = 2, int $stock = 10): Order
{
    static $n = 0;
    $n++;
    $variant = adminOrderVariant('AO'.$n, $stock);

    $order = Order::create(array_merge([
        'contact_name' => 'Nour Ali',
        'contact_mobile' => '01500060828',
        'contact_email' => 'nour@example.com',
        'is_company' => false,
        'locale' => 'en',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::Pending,
        'payment_method_code' => 'cod',
        'delivery_method_name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'subtotal' => 1000 * $qty,
        'discount_total' => 0,
        'delivery_fee' => 150,
        'total' => 1000 * $qty + 150,
        'vat_rate' => 14,
        'vat_amount' => round((1000 * $qty + 150) * 14 / 114, 2),
        'placed_at' => now(),
    ], $attributes));

    $order->items()->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'منتج', 'en' => 'Product'],
        'variant_label' => ['ar' => '', 'en' => ''],
        'sku' => $variant->sku,
        'unit_price' => 1000,
        'qty' => $qty,
        'line_total' => 1000 * $qty,
    ]);

    $order->statusHistories()->create(['status' => OrderStatus::New->value, 'created_at' => now()]);

    return $order->refresh();
}

it('gates the orders list behind orders.view', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.orders'))->assertForbidden();

    loginAdmin(['orders.view']);
    $this->get(route('admin.orders'))->assertOk();
});

it('lists orders and filters by status tab', function () {
    adminOrder(['status' => OrderStatus::New]);
    adminOrder(['status' => OrderStatus::Delivered]);

    Livewire::test(Index::class)
        ->assertSee('Nour Ali')
        ->set('tab', 'delivered')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->status === OrderStatus::Delivered);
});

it('filters by payment status, method and search', function () {
    adminOrder(['payment_status' => OrderPaymentStatus::Paid, 'payment_method_code' => 'card', 'contact_name' => 'Alpha One']);
    adminOrder(['payment_status' => OrderPaymentStatus::Pending, 'payment_method_code' => 'cod', 'contact_name' => 'Beta Two']);

    Livewire::test(Index::class)
        ->set('payment', 'paid')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('payment', '')
        ->set('method', 'cod')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->payment_method_code === 'cod')
        ->set('method', '')
        ->set('search', 'Alpha')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->contact_name === 'Alpha One');
});

it('exports matching orders as a CSV download', function () {
    adminOrder(['contact_name' => 'Export Me', 'status' => OrderStatus::New]);
    adminOrder(['contact_name' => 'Skip Me', 'status' => OrderStatus::Delivered]);

    $component = Livewire::test(Index::class)->set('tab', 'new');
    $component->call('export')->assertFileDownloaded('orders-'.now()->format('Y-m-d').'.csv');

    // Capture the streamed CSV body to assert the filter was applied.
    $response = $component->instance()->export();
    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)->toContain('Export Me')
        ->and($csv)->not->toContain('Skip Me');
});

it('gates the detail behind orders.view', function () {
    $order = adminOrder();

    loginAdmin(['dashboard.view']);
    $this->get(route('admin.orders.show', $order))->assertForbidden();

    loginAdmin(['orders.view']);
    $this->get(route('admin.orders.show', $order))->assertOk();
});

it('a status change writes a history row with notes and the admin user', function () {
    $admin = loginAdmin(['orders.view', 'orders.update']);
    $order = adminOrder(['status' => OrderStatus::New]);

    Livewire::test(Detail::class, ['order' => $order])
        ->set('status', 'confirmed')
        ->set('customerNoteEn', 'On its way')
        ->set('customerNoteAr', 'في الطريق')
        ->set('internalNote', 'Called the customer')
        ->call('update')
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Confirmed);

    // reorder() clears the relation's default created_at ordering so the newest
    // row is picked by id alone (created_at is second-precision and can tie).
    $latest = $order->statusHistories()->reorder('id', 'desc')->first();
    expect($latest->status)->toBe('confirmed')
        ->and($latest->user_id)->toBe($admin->id)
        ->and($latest->getTranslation('note', 'en'))->toBe('On its way')
        ->and($latest->internal_note)->toBe('Called the customer');
});

it('does nothing when no fields changed', function () {
    loginAdmin(['orders.view', 'orders.update']);
    $order = adminOrder(['status' => OrderStatus::New]);
    $before = $order->statusHistories()->count();

    Livewire::test(Detail::class, ['order' => $order])
        ->call('update');

    expect($order->statusHistories()->count())->toBe($before);
});

it('cancelling an order restores the reserved stock exactly once', function () {
    loginAdmin(['orders.view', 'orders.update']);
    $order = adminOrder(['status' => OrderStatus::Confirmed], qty: 3, stock: 7);
    $variant = $order->items->first()->productVariant;
    expect($variant->stock_qty)->toBe(7);

    Livewire::test(Detail::class, ['order' => $order])
        ->set('status', 'cancelled')
        ->call('update')
        ->assertHasNoErrors();

    expect($variant->refresh()->stock_qty)->toBe(10);

    // Re-saving a cancelled order must not restore stock again.
    Livewire::test(Detail::class, ['order' => $order->refresh()])
        ->set('status', 'cancelled')
        ->set('internalNote', 'still cancelled')
        ->call('update');

    expect($variant->refresh()->stock_qty)->toBe(10);
});

it('blocks status updates without orders.update', function () {
    loginAdmin(['orders.view']);
    $order = adminOrder();

    Livewire::test(Detail::class, ['order' => $order])
        ->set('status', 'confirmed')
        ->call('update')
        ->assertForbidden();
});
