<?php

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\QuoteSource;
use App\Models\ConsultationRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Payment;
use App\Models\QuoteRequest;
use Database\Seeders\AttributesSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CommerceSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\GovernoratesSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(GovernoratesSeeder::class);
    $this->seed(AttributesSeeder::class);
    $this->seed(CatalogSeeder::class);
    $this->seed(CommerceSeeder::class);
    $this->seed(DemoSeeder::class);
});

it('seeds demo customers with addresses and wishlists', function () {
    expect(Customer::count())->toBe(4)
        ->and(Customer::where('email', 'ahmed.samir@gmail.com')->exists())->toBeTrue();

    $ahmed = Customer::where('email', 'ahmed.samir@gmail.com')->firstOrFail();
    expect($ahmed->addresses()->count())->toBe(1)
        ->and($ahmed->wishlist()->count())->toBeGreaterThan(0)
        ->and(Customer::where('status', 'disabled')->count())->toBe(1);
});

it('seeds orders covering every status and payment status, guest and customer', function () {
    expect(Order::count())->toBe(8)
        ->and(Order::whereNull('customer_id')->count())->toBe(2); // guests

    // Every order has a number, items and a 1:1 address.
    Order::with(['items', 'address'])->get()->each(function (Order $order) {
        expect($order->number)->toStartWith('DT-')
            ->and($order->items->count())->toBeGreaterThan(0)
            ->and($order->address)->not->toBeNull()
            ->and((float) $order->total)->toBeGreaterThan(0);
    });

    expect(Order::where('status', OrderStatus::Delivered)->exists())->toBeTrue()
        ->and(Order::where('status', OrderStatus::Cancelled)->exists())->toBeTrue()
        ->and(Order::where('payment_status', OrderPaymentStatus::AwaitingTransfer)->exists())->toBeTrue()
        ->and(Order::where('payment_status', OrderPaymentStatus::Refunded)->exists())->toBeTrue()
        ->and(Order::where('payment_status', OrderPaymentStatus::Failed)->exists())->toBeTrue();
});

it('logs Paymob payments and records status-history timelines', function () {
    expect(Payment::where('gateway', 'paymob')->count())->toBeGreaterThan(0)
        ->and(Payment::where('failure_code', '51')->exists())->toBeTrue();

    $delivered = Order::where('status', OrderStatus::Delivered)->first();
    expect($delivered->statusHistories()->count())->toBeGreaterThanOrEqual(5);
});

it('seeds coupons used on some orders', function () {
    expect(Coupon::where('code', 'DOTS10')->firstOrFail()->used_count)->toBeGreaterThan(0)
        ->and(Order::whereNotNull('coupon_id')->count())->toBeGreaterThan(0);
});

it('seeds a WhatsApp lead for every source, plus consultations and newsletter', function () {
    foreach (QuoteSource::cases() as $source) {
        expect(QuoteRequest::where('source', $source->value)->exists())->toBeTrue();
    }

    expect(ConsultationRequest::count())->toBe(4)
        ->and(NewsletterSubscriber::count())->toBe(3);
});
