<?php

use App\Enums\AddressLabel;
use App\Enums\ConsultationAreaRange;
use App\Enums\ConsultationProjectType;
use App\Enums\CouponType;
use App\Enums\CustomerStatus;
use App\Enums\LeadStatus;
use App\Enums\NewsletterSource;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\PaymentStatus;
use App\Enums\QuoteSource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ConsultationRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Governorate;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\QuoteRequest;

function leafProduct(string $sku): Product
{
    $brand = Brand::create(['name' => 'B'.$sku, 'slug' => 'b-'.$sku]);
    $category = Category::create(['level' => 3, 'name' => ['ar' => 'ق', 'en' => 'C'], 'slug' => ['ar' => 'ق-'.$sku, 'en' => 'c-'.$sku]]);

    return Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => ['ar' => 'م', 'en' => 'P'],
        'slug' => ['ar' => 'م-'.$sku, 'en' => 'p-'.$sku],
        'sku' => $sku,
    ]);
}

it('casts order and payment-method enums and snapshots translatable fields', function () {
    $pm = PaymentMethod::create(['code' => PaymentMethodCode::Cod, 'name' => ['ar' => 'عند الاستلام', 'en' => 'COD']]);
    $order = Order::create([
        'contact_name' => 'Sara',
        'contact_mobile' => '01111111111',
        'payment_method_id' => $pm->id,
        'status' => OrderStatus::Confirmed,
        'payment_status' => OrderPaymentStatus::Paid,
        'delivery_method_name' => ['ar' => 'توصيل', 'en' => 'Courier'],
    ]);

    expect($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(OrderPaymentStatus::Paid)
        ->and($pm->code)->toBe(PaymentMethodCode::Cod)
        ->and($order->getTranslation('delivery_method_name', 'en'))->toBe('Courier');
});

it('wires order relations: items, address, histories and payments', function () {
    $product = leafProduct('O-1');
    $variant = $product->variants()->create(['sku' => 'O-1-A', 'price' => 50]);
    $gov = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo']]);

    $order = Order::create(['contact_name' => 'A', 'contact_mobile' => '01000000000']);
    $order->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => ['ar' => 'م', 'en' => 'P'],
        'sku' => 'O-1-A',
        'unit_price' => 50,
        'qty' => 2,
        'line_total' => 100,
    ]);
    $order->address()->create([
        'recipient_name' => 'A',
        'mobile' => '01000000000',
        'governorate_id' => $gov->id,
        'governorate_name' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
        'city' => 'Nasr City',
        'street' => 'St 1',
    ]);
    $order->statusHistories()->create(['status' => 'confirmed', 'note' => ['ar' => 'تم', 'en' => 'Done']]);
    $order->payments()->create(['gateway' => 'paymob', 'amount' => 100, 'status' => PaymentStatus::Paid]);

    $order->refresh();

    expect($order->items)->toHaveCount(1)
        ->and($order->items->first()->line_total)->toBe('100.00')
        ->and($order->address->city)->toBe('Nasr City')
        ->and($order->statusHistories->first()->getTranslation('note', 'en'))->toBe('Done')
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Paid);
});

it('links a delivery method to governorates (empty = everywhere)', function () {
    $method = DeliveryMethod::create(['name' => ['ar' => 'توصيل', 'en' => 'Courier'], 'price' => 60]);
    $cairo = Governorate::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo']]);
    $method->governorates()->attach($cairo);

    expect($method->governorates->first()->is($cairo))->toBeTrue()
        ->and($method->price)->toBe('60.00');
});

it('casts coupon type and filters valid coupons', function () {
    Coupon::create(['code' => 'DOTS10', 'type' => CouponType::Percent, 'value' => 10, 'is_active' => true]);
    Coupon::create(['code' => 'OLD', 'type' => CouponType::Fixed, 'value' => 50, 'is_active' => true, 'ends_at' => now()->subDay()]);
    Coupon::create(['code' => 'OFF', 'type' => CouponType::Fixed, 'value' => 50, 'is_active' => false]);

    expect(Coupon::valid()->pluck('code')->all())->toBe(['DOTS10'])
        ->and(Coupon::where('code', 'DOTS10')->first()->type)->toBe(CouponType::Percent);
});

it('wires customer addresses, orders and wishlist with enum + soft deletes', function () {
    $customer = Customer::create([
        'name' => 'Omar', 'mobile' => '01000000001', 'email' => 'omar@example.com',
        'password' => 'secret12', 'status' => CustomerStatus::Active,
    ]);
    $gov = Governorate::create(['name' => ['ar' => 'الجيزة', 'en' => 'Giza']]);
    $customer->addresses()->create([
        'label' => AddressLabel::Home, 'recipient_name' => 'Omar', 'mobile' => '01000000001',
        'governorate_id' => $gov->id, 'city' => 'Dokki', 'street' => 'St 2',
    ]);
    Order::create(['contact_name' => 'Omar', 'contact_mobile' => '01000000001', 'customer_id' => $customer->id]);
    $customer->wishlist()->attach(leafProduct('W-1'));

    expect($customer->status)->toBe(CustomerStatus::Active)
        ->and($customer->addresses->first()->label)->toBe(AddressLabel::Home)
        ->and($customer->orders)->toHaveCount(1)
        ->and($customer->wishlist)->toHaveCount(1);

    $customer->delete();
    expect(Customer::withTrashed()->count())->toBe(1)
        ->and(Customer::count())->toBe(0);
});

it('casts lead enums on quote, consultation and newsletter records', function () {
    $quote = QuoteRequest::create(['source' => QuoteSource::Product, 'status' => LeadStatus::New]);
    $consult = ConsultationRequest::create([
        'name' => 'Lead', 'mobile' => '01000000002',
        'project_type' => ConsultationProjectType::Villa, 'area_range' => ConsultationAreaRange::R150To400,
    ]);
    $sub = NewsletterSubscriber::create(['email' => 'news@example.com', 'source' => NewsletterSource::Footer]);

    expect($quote->source)->toBe(QuoteSource::Product)
        ->and($quote->status)->toBe(LeadStatus::New)
        ->and($consult->area_range)->toBe(ConsultationAreaRange::R150To400)
        ->and($consult->area_range->value)->toBe('150_400')
        ->and($sub->source)->toBe(NewsletterSource::Footer);
});
