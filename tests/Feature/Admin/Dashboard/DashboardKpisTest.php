<?php

use App\Enums\LeadStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\QuoteSource;
use App\Livewire\Admin\Dashboard;
use App\Models\ConsultationRequest;
use App\Models\Order;
use App\Models\QuoteRequest;
use Livewire\Livewire;

beforeEach(fn () => loginAdmin());

function dashboardOrder(array $attributes = []): Order
{
    static $n = 0;
    $n++;

    return Order::create(array_merge([
        'number' => 'DT-'.(20000 + $n),
        'contact_name' => 'Buyer '.$n,
        'contact_mobile' => '01500060828',
        'is_company' => false,
        'locale' => 'en',
        'status' => OrderStatus::New,
        'payment_status' => OrderPaymentStatus::Pending,
        'payment_method_code' => 'cod',
        'delivery_method_name' => ['ar' => 'قياسي', 'en' => 'Standard'],
        'subtotal' => 1000,
        'discount_total' => 0,
        'delivery_fee' => 0,
        'total' => 1000,
        'vat_rate' => 14,
        'vat_amount' => 122.81,
        'placed_at' => now(),
    ], $attributes));
}

it('renders the dashboard with KPI data', function () {
    dashboardOrder(['placed_at' => now()]);
    dashboardOrder(['payment_status' => OrderPaymentStatus::Paid, 'total' => 5000, 'placed_at' => now()]);

    QuoteRequest::create([
        'source' => QuoteSource::Product, 'status' => LeadStatus::New,
        'contact_name' => 'Lead', 'locale' => 'en', 'created_at' => now(),
    ]);
    ConsultationRequest::create([
        'name' => 'Sara', 'mobile' => '01500060828',
        'project_type' => 'villa', 'area_range' => '150_400',
        'locale' => 'en', 'status' => LeadStatus::New,
    ]);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertViewHas('ordersToday', 2)
        ->assertViewHas('revenueMonth', 5000.0)
        ->assertViewHas('whatsappToday', 1)
        ->assertViewHas('consultationsNew', 1);
});

it('renders the dashboard route', function () {
    $this->get(route('admin.dashboard'))->assertOk();
});
