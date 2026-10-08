<?php

namespace Database\Seeders;

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
use App\Models\ConsultationRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryMethod;
use App\Models\Governorate;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic demo data for the admin + account areas (Account.jsx, Orders.jsx,
 * admin-kit A_ORDERS/A_LEADS/A_CONSULTS). Never runs automatically in
 * production; call explicitly with `--class=DemoSeeder`, or it is included by
 * DatabaseSeeder only on local/staging.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (ProductVariant::whereNotNull('price')->doesntExist()) {
            $this->command->warn('Run the design seeders first (products with priced variants).');

            return;
        }

        $customers = $this->seedCustomers();
        $coupons = $this->seedCoupons();
        $this->seedOrders($customers, $coupons);
        $this->seedLeads($customers);

        $this->command->info('Demo customer login: '.$customers[0]->email.' / password');
        $this->command->info('Admin login: '.config('dots.admin.email').' / (ADMIN_PASSWORD)');
    }

    /** @return array<int, Customer> */
    private function seedCustomers(): array
    {
        $cairo = Governorate::where('name->en', 'Cairo')->first();
        $data = [
            ['Ahmed Samir', 'ahmed.samir@gmail.com', '01001234567'],
            ['Mona Khaled', 'mona.khaled@gmail.com', '01122334455'],
            ['Youssef Adel', 'youssef.adel@outlook.com', '01234567890'],
            ['Sara Mostafa', 'sara.mostafa@gmail.com', '01555667788'],
        ];

        $customers = [];
        foreach ($data as $i => [$name, $email, $mobile]) {
            $customer = Customer::updateOrCreate(['email' => $email], [
                'name' => $name,
                'mobile' => $mobile,
                'password' => Hash::make('password'),
                'status' => $i === 3 ? CustomerStatus::Disabled : CustomerStatus::Active,
                'created_at' => now()->subDays(random_int(40, 300)),
            ]);

            $customer->addresses()->delete();
            $customer->addresses()->create([
                'label' => 'home',
                'recipient_name' => $name,
                'mobile' => $mobile,
                'governorate_id' => $cairo?->id,
                'city' => 'Nasr City',
                'street' => "{$i} Abbas El Akkad St",
                'floor_apartment' => 'Floor '.($i + 1),
                'is_default' => true,
            ]);

            // Wishlist (KA_WISH_START).
            $wishlist = Product::query()->inRandomOrder()->take(random_int(1, 3))->pluck('id');
            $customer->wishlist()->sync($wishlist);

            $customers[] = $customer;
        }

        return $customers;
    }

    /** @return array<string, Coupon> */
    private function seedCoupons(): array
    {
        return [
            'DOTS10' => Coupon::updateOrCreate(['code' => 'DOTS10'], [
                'type' => CouponType::Percent, 'value' => 10, 'min_order_total' => 1000,
                'usage_limit' => 100, 'once_per_customer' => true, 'is_active' => true,
            ]),
            'FREESHIP' => Coupon::updateOrCreate(['code' => 'FREESHIP'], [
                'type' => CouponType::Fixed, 'value' => 150, 'min_order_total' => 2000, 'is_active' => true,
            ]),
            'WELCOME50' => Coupon::updateOrCreate(['code' => 'WELCOME50'], [
                'type' => CouponType::Fixed, 'value' => 50, 'is_active' => true,
            ]),
        ];
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<string, Coupon>  $coupons
     */
    private function seedOrders(array $customers, array $coupons): void
    {
        $cod = PaymentMethod::where('code', PaymentMethodCode::Cod)->first();
        $card = PaymentMethod::where('code', PaymentMethodCode::Card)->first();
        $instapay = PaymentMethod::where('code', PaymentMethodCode::InstapayWhatsapp)->first();
        $standard = DeliveryMethod::where('name->en', 'Standard delivery')->first();

        // Each row: [customer index|null guest, method, status, payment_status, couponCode|null, withPayment, paymentStatus]
        $orders = [
            [0, $cod, OrderStatus::Delivered, OrderPaymentStatus::Paid, 'DOTS10', null],
            [0, $card, OrderStatus::Confirmed, OrderPaymentStatus::Paid, null, PaymentStatus::Paid],
            [1, $instapay, OrderStatus::New, OrderPaymentStatus::AwaitingTransfer, null, null],
            [1, $card, OrderStatus::New, OrderPaymentStatus::Failed, null, PaymentStatus::Failed],
            [2, $cod, OrderStatus::Preparing, OrderPaymentStatus::Pending, 'FREESHIP', null],
            [2, $card, OrderStatus::Cancelled, OrderPaymentStatus::Refunded, null, PaymentStatus::Refunded],
            [null, $cod, OrderStatus::Shipped, OrderPaymentStatus::Pending, null, null], // guest
            [null, $instapay, OrderStatus::Delivered, OrderPaymentStatus::Paid, 'WELCOME50', null], // guest
        ];

        foreach ($orders as $i => [$ci, $method, $status, $paymentStatus, $couponCode, $paymentState]) {
            $customer = $ci !== null ? $customers[$ci] : null;
            $placedAt = now()->subDays(random_int(1, 60));
            $coupon = $couponCode ? $coupons[$couponCode] : null;

            $this->makeOrder($customer, $method, $standard, $status, $paymentStatus, $coupon, $paymentState, $placedAt);
        }
    }

    private function makeOrder(
        ?Customer $customer,
        ?PaymentMethod $method,
        ?DeliveryMethod $delivery,
        OrderStatus $status,
        OrderPaymentStatus $paymentStatus,
        ?Coupon $coupon,
        ?PaymentStatus $paymentState,
        Carbon $placedAt,
    ): void {
        $variants = ProductVariant::with('product')->whereNotNull('price')->inRandomOrder()->take(random_int(1, 3))->get();
        $cairo = Governorate::where('name->en', 'Cairo')->first();

        $name = $customer !== null ? $customer->name : 'Guest Buyer';
        $mobile = $customer !== null ? $customer->mobile : '01000000000';

        $order = new Order([
            'customer_id' => $customer?->id,
            'contact_name' => $name,
            'contact_mobile' => $mobile,
            'contact_email' => $customer?->email,
            'locale' => 'ar',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method_id' => $method?->id,
            'payment_method_code' => $method?->code->value,
            'delivery_method_id' => $delivery?->id,
            'delivery_method_name' => $delivery?->getTranslations('name'),
            'vat_rate' => 14,
            'placed_at' => $placedAt,
            'reserved_until' => $paymentStatus === OrderPaymentStatus::AwaitingTransfer ? $placedAt->copy()->addHours(24) : null,
        ]);
        $order->created_at = $placedAt;
        $order->save();

        $subtotal = 0;
        foreach ($variants as $variant) {
            $product = $variant->product;
            $qty = random_int(1, 4);
            $unit = (float) $variant->price;
            $line = round($unit * $qty, 2);
            $subtotal += $line;

            $order->items()->create([
                'product_id' => $product?->id,
                'product_variant_id' => $variant->id,
                'product_name' => $product?->getTranslations('name') ?? ['en' => 'Product', 'ar' => 'منتج'],
                'variant_label' => $product?->getTranslations('variants_summary') ?: null,
                'sku' => $variant->sku,
                'image_url' => $product?->getFirstMediaUrl('gallery') ?: null,
                'unit_price' => $unit,
                'qty' => $qty,
                'line_total' => $line,
            ]);
        }

        $discount = 0.0;
        if ($coupon) {
            $discount = $coupon->type === CouponType::Percent
                ? round($subtotal * (float) $coupon->value / 100, 2)
                : (float) $coupon->value;
            $coupon->increment('used_count');
            $coupon->redemptions()->create([
                'order_id' => $order->id, 'customer_id' => $customer?->id, 'amount' => $discount,
                'created_at' => $placedAt,
            ]);
        }

        $deliveryFee = $delivery && ! ($delivery->free_over_amount && $subtotal >= (float) $delivery->free_over_amount)
            ? (float) $delivery->price : 0.0;
        $total = round($subtotal - $discount + $deliveryFee, 2);

        $order->forceFill([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'vat_amount' => round($total * 14 / 114, 2),
        ])->save();

        $order->address()->create([
            'recipient_name' => $name,
            'mobile' => $mobile,
            'governorate_id' => $cairo?->id,
            'governorate_name' => $cairo?->getTranslations('name') ?? ['en' => 'Cairo', 'ar' => 'القاهرة'],
            'city' => 'Nasr City',
            'street' => '12 Makram Ebeid St',
            'floor_apartment' => 'Floor 3',
        ]);

        $this->seedStatusHistory($order, $status, $placedAt);

        if ($paymentState !== null && $method) {
            $order->payments()->create([
                'gateway' => 'paymob',
                'gateway_ref' => 'intent_'.$order->id,
                'transaction_id' => 'txn_'.$order->id,
                'amount' => $total,
                'currency' => 'EGP',
                'status' => $paymentState,
                'card_brand' => 'visa',
                'card_last4' => '4242',
                'failure_code' => $paymentState === PaymentStatus::Failed ? '51' : null,
                'failure_message' => $paymentState === PaymentStatus::Failed ? 'Insufficient funds' : null,
                'hmac_verified' => true,
                'raw' => ['demo' => true, 'order' => $order->number],
                'created_at' => $placedAt,
            ]);
        }
    }

    private function seedStatusHistory(Order $order, OrderStatus $status, Carbon $placedAt): void
    {
        // Timeline from "new" up to the current status.
        $flow = [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Shipped, OrderStatus::Delivered];
        $notes = [
            'new' => ['en' => 'Order placed.', 'ar' => 'تم استلام الطلب.'],
            'confirmed' => ['en' => 'Order confirmed.', 'ar' => 'تم تأكيد الطلب.'],
            'preparing' => ['en' => 'Preparing your order.', 'ar' => 'جارٍ تجهيز الطلب.'],
            'shipped' => ['en' => 'Out for delivery.', 'ar' => 'خرج للتوصيل.'],
            'delivered' => ['en' => 'Delivered.', 'ar' => 'تم التوصيل.'],
            'cancelled' => ['en' => 'Order cancelled.', 'ar' => 'تم إلغاء الطلب.'],
        ];

        if ($status === OrderStatus::Cancelled) {
            $sequence = [OrderStatus::New, OrderStatus::Cancelled];
        } else {
            $sequence = [];
            foreach ($flow as $step) {
                $sequence[] = $step;
                if ($step === $status) {
                    break;
                }
            }
        }

        foreach ($sequence as $s => $step) {
            $order->statusHistories()->create([
                'status' => $step->value,
                'note' => $notes[$step->value],
                'created_at' => $placedAt->copy()->addHours($s * 6),
            ]);
        }
    }

    /** @param  array<int, Customer>  $customers */
    private function seedLeads(array $customers): void
    {
        $service = Service::first();

        // A quote request for every source (A_LEADS).
        foreach (QuoteSource::cases() as $i => $source) {
            QuoteRequest::create([
                'source' => $source,
                'customer_id' => $i % 3 === 0 ? ($customers[0]->id ?? null) : null,
                'contact_name' => $i % 2 === 0 ? 'Lead '.$i : null,
                'contact_mobile' => '0100000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'page_url' => 'https://dots.eg/'.$source->value,
                'locale' => $i % 2 === 0 ? 'ar' : 'en',
                'status' => LeadStatus::cases()[$i % count(LeadStatus::cases())],
                'created_at' => now()->subDays(random_int(1, 60)),
            ]);
        }

        // Consultation requests (A_CONSULTS).
        $consults = [
            ['Khaled Ibrahim', '01099887766', ConsultationProjectType::Villa, ConsultationAreaRange::R400To1000],
            ['Dina Fouad', '01088776655', ConsultationProjectType::Apartment, ConsultationAreaRange::Lt150],
            ['Omar Nabil', '01077665544', ConsultationProjectType::Hotel, ConsultationAreaRange::Gt1000],
            ['Hana Said', '01066554433', ConsultationProjectType::RetailFnb, ConsultationAreaRange::R150To400],
        ];
        foreach ($consults as $i => [$name, $mobile, $type, $area]) {
            ConsultationRequest::create([
                'service_id' => $service?->id,
                'name' => $name,
                'mobile' => $mobile,
                'project_type' => $type,
                'area_range' => $area,
                'notes' => 'Interested in a full lighting plan.',
                'locale' => 'ar',
                'status' => LeadStatus::cases()[$i % count(LeadStatus::cases())],
                'created_at' => now()->subDays(random_int(1, 60)),
            ]);
        }

        // Newsletter subscribers.
        foreach (['laila@example.com', 'tarek@example.com', 'nada@example.com'] as $i => $email) {
            NewsletterSubscriber::updateOrCreate(['email' => $email], [
                'locale' => $i % 2 === 0 ? 'ar' : 'en',
                'source' => $i === 0 ? NewsletterSource::Blog : NewsletterSource::Footer,
                'created_at' => now()->subDays(random_int(1, 60)),
            ]);
        }
    }
}
