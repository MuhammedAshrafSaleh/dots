<?php

use App\Actions\PlaceOrder;
use App\Enums\PaymentMethodCode;
use App\Enums\ProductStatus;
use App\Mail\NewOrderMail;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use App\Services\Settings;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function nmVariant(string $sku, float $price = 500): ProductVariant
{
    $brand = Brand::create(['name' => 'NM'.$sku, 'slug' => 'nm-'.strtolower($sku)]);
    $category = Category::create([
        'level' => 3,
        'name' => ['ar' => 'ق', 'en' => 'C'],
        'slug' => ['ar' => 'قن-'.$sku, 'en' => 'cn-'.strtolower($sku)],
    ]);
    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'منتج '.$sku, 'en' => 'Product '.$sku],
        'slug' => ['ar' => 'مp-'.$sku, 'en' => 'pn-'.strtolower($sku)],
        'sku' => $sku,
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'price_on_request' => false,
        'min_price' => $price,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'sku' => $sku.'-V',
        'price' => $price,
        'stock_qty' => 10,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);
}

it('queues NewOrderMail to the contact email after a successful order', function () {
    $settings = app(Settings::class);
    $settings->set('contact.email', 'admin@dots.test');

    $variant = nmVariant('NM1');

    $delivery = DeliveryMethod::create([
        'name' => ['ar' => 'توصيل', 'en' => 'Delivery'],
        'icon' => 'truck',
        'price' => 0,
        'requires_address' => false,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $payment = PaymentMethod::create([
        'code' => PaymentMethodCode::Cod,
        'name' => ['ar' => 'عند الاستلام', 'en' => 'Cash on Delivery'],
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $cart = app(CartManager::class);
    $cart->add($variant->id, 1);

    $contact = [
        'name' => 'Test Customer',
        'mobile' => '01012345678',
        'email' => null,
        'is_company' => false,
        'company_tax_id' => null,
    ];

    $order = app(PlaceOrder::class)->handle($delivery, $payment, $contact, null);

    Mail::assertQueued(NewOrderMail::class, function (NewOrderMail $mail) use ($order) {
        $hasRecipient = collect($mail->to)->pluck('address')->contains('admin@dots.test');

        return $mail->order->id === $order->id && $hasRecipient;
    });
});

it('skips the mail when no contact email is configured', function () {
    $settings = app(Settings::class);
    $settings->set('contact.email', '');

    $variant = nmVariant('NM2');

    $delivery = DeliveryMethod::create([
        'name' => ['ar' => 'توصيل', 'en' => 'Delivery2'],
        'icon' => 'truck',
        'price' => 0,
        'requires_address' => false,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $payment = PaymentMethod::create([
        'code' => PaymentMethodCode::Cod,
        'name' => ['ar' => 'كاش', 'en' => 'COD2'],
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $cart = app(CartManager::class);
    $cart->add($variant->id, 1);

    app(PlaceOrder::class)->handle($delivery, $payment, [
        'name' => 'Guest',
        'mobile' => '01099999999',
        'email' => null,
        'is_company' => false,
        'company_tax_id' => null,
    ], null);

    Mail::assertNothingQueued();
});
