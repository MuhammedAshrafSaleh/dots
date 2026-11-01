<?php

namespace App\Console\Commands;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * One-shot prep for the client demo: creates a demo customer and a card order
 * already in the failed-payment state, then prints the login credentials and
 * the exact URL to open before the appointment.
 *
 * Safe to re-run — each run creates a fresh order; the demo customer is
 * upserted so credentials never change.
 */
class DemoFailedPayment extends Command
{
    protected $signature = 'demo:failed-payment';

    protected $description = 'Prepare a card order in the failed-payment state for the client demo.';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('DemoFailedPayment does not run in production.');

            return self::FAILURE;
        }

        // Upsert demo customer — stable credentials across re-runs.
        $customer = Customer::updateOrCreate(
            ['email' => 'demo@dots.test'],
            [
                'name' => 'عميل تجريبي',
                'mobile' => '01012345699',
                'password' => Hash::make('Demo1234!'),
                'email_verified_at' => now(),
            ]
        );

        // Card payment method — nullable; the page still shows all three
        // recovery options even when the method row is absent.
        $card = PaymentMethod::where('code', 'card')->first();

        // Any in-stock active variant — we just need a real product snapshot.
        $variant = ProductVariant::with('product')
            ->where('stock_qty', '>', 0)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->first();

        if ($variant === null) {
            $this->error('No active in-stock product variants found. Add stock to at least one product and try again.');

            return self::FAILURE;
        }

        $product = $variant->product;
        $price = round((float) $variant->price, 2);
        $fee = 50.00;
        $subtotal = $price;
        $vatRate = 14.00;
        // VAT is included in the price; extract it.
        $vatAmt = round($subtotal * $vatRate / (100 + $vatRate), 2);
        $total = round($subtotal + $fee, 2);

        $order = Order::create([
            'customer_id' => $customer->id,
            'contact_name' => $customer->name,
            'contact_mobile' => $customer->mobile,
            'contact_email' => $customer->email,
            'locale' => 'ar',
            'status' => OrderStatus::New,
            'payment_status' => OrderPaymentStatus::Failed,
            'payment_method_id' => $card?->id,
            'payment_method_code' => 'card',
            'delivery_method_name' => ['ar' => 'توصيل إلى الباب', 'en' => 'Home delivery'],
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'delivery_fee' => $fee,
            'total' => $total,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmt,
            'placed_at' => now()->subMinutes(10),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->getTranslations('name'),
            'variant_label' => ['ar' => '', 'en' => ''],
            'sku' => $variant->sku ?? 'DEMO',
            'image_url' => $product->getFirstMediaUrl('gallery') ?: null,
            'unit_price' => $price,
            'qty' => 1,
            'line_total' => $price,
        ]);

        $order->statusHistories()->create([
            'status' => OrderStatus::New->value,
            'created_at' => now()->subMinutes(10),
        ]);

        $url = 'http://dots.test/ar/order/'.$order->number.'/payment-failed';

        $this->newLine();
        $this->info('✓  Demo order ready: '.$order->number);
        $this->newLine();
        $this->line('  Before your appointment (do this once in the demo browser):');
        $this->newLine();
        $this->line('  1. Log in at  http://dots.test/ar/login');
        $this->line('     Email      demo@dots.test');
        $this->line('     Password   Demo1234!');
        $this->newLine();
        $this->line('  2. Open this URL and keep the tab open:');
        $this->line('     '.$url);
        $this->newLine();
        $this->line('  During the demo, switch to that tab — the page is already loaded.');
        $this->newLine();

        return self::SUCCESS;
    }
}
