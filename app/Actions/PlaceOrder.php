<?php

namespace App\Actions;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Exceptions\CheckoutException;
use App\Mail\NewOrderMail;
use App\Models\CartItem;
use App\Models\CouponRedemption;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Services\CartManager;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Places an order from the current cart inside a single DB transaction:
 * decrements stock (never below zero), snapshots product/variant/price/address
 * data, redeems the coupon, writes the first history row and assigns the number.
 */
class PlaceOrder
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly Settings $settings,
        private readonly UpdateProductCacheColumns $cache,
    ) {}

    /**
     * @param  array{name: string, mobile: string, email: ?string, is_company: bool, company_tax_id: ?string}  $contact
     * @param  array{recipient_name: string, mobile: string, governorate_id: ?int, governorate_name: array<string, string>, city: string, street: string, floor_apartment: ?string, landmark: ?string}|null  $address
     */
    public function handle(DeliveryMethod $delivery, PaymentMethod $payment, array $contact, ?array $address): Order
    {
        $items = $this->cart->items();
        if ($items->isEmpty()) {
            throw CheckoutException::emptyCart();
        }

        $locale = app()->getLocale();
        $coupon = $this->cart->activeCoupon();
        $totals = $this->cart->totalsForDelivery($delivery);
        $customerId = $this->cart->customerId();

        $order = DB::transaction(function () use ($items, $delivery, $payment, $contact, $address, $locale, $coupon, $totals, $customerId): Order {
            $instapay = $payment->code === PaymentMethodCode::InstapayWhatsapp;
            $card = $payment->code === PaymentMethodCode::Card;

            $order = Order::create([
                'customer_id' => $customerId,
                'contact_name' => $contact['name'],
                'contact_mobile' => $contact['mobile'],
                'contact_email' => $contact['email'],
                'is_company' => $contact['is_company'],
                'company_tax_id' => $contact['is_company'] ? $contact['company_tax_id'] : null,
                'locale' => $locale,
                'status' => OrderStatus::New,
                'payment_status' => $instapay ? OrderPaymentStatus::AwaitingTransfer : OrderPaymentStatus::Pending,
                'payment_method_id' => $payment->id,
                'payment_method_code' => $payment->code->value,
                'delivery_method_id' => $delivery->id,
                'delivery_method_name' => $delivery->getTranslations('name'),
                'subtotal' => $totals->subtotal,
                'discount_total' => $totals->discount,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'delivery_fee' => $totals->deliveryFee,
                'total' => $totals->total,
                'vat_rate' => $totals->vatRate,
                'vat_amount' => $totals->vat,
                // reserved_until is the universal pay-by deadline the sweeper
                // reads. InstaPay keeps its transfer window; card orders get a
                // payment window so an unpaid (or checkout-throwing) card order
                // still returns its stock; COD stays null → never swept.
                'reserved_until' => match (true) {
                    $instapay => now()->addHours($this->holdHours()),
                    $card => now()->addMinutes($this->cardWindowMinutes()),
                    default => null,
                },
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
                'placed_at' => now(),
            ]);

            $this->createItems($order, $items);

            if ($delivery->requires_address && $address !== null) {
                $order->address()->create([
                    'recipient_name' => $address['recipient_name'],
                    'mobile' => $address['mobile'],
                    'governorate_id' => $address['governorate_id'],
                    'governorate_name' => $address['governorate_name'],
                    'city' => $address['city'],
                    'street' => $address['street'],
                    'floor_apartment' => $address['floor_apartment'],
                    'landmark' => $address['landmark'],
                ]);
            }

            if ($coupon !== null && $totals->discount > 0) {
                CouponRedemption::create([
                    'coupon_id' => $coupon->id,
                    'order_id' => $order->id,
                    'customer_id' => $customerId,
                    'amount' => $totals->discount,
                    'created_at' => now(),
                ]);
                $coupon->increment('used_count');
            }

            $order->statusHistories()->create([
                'status' => OrderStatus::New->value,
                'created_at' => now(),
            ]);

            $this->cart->clear();

            return $order->refresh();
        });

        $this->dispatchNewOrderMail($order);

        return $order;
    }

    private function dispatchNewOrderMail(Order $order): void
    {
        $to = (string) ($this->settings->get('contact.email') ?? '');
        if ($to === '') {
            return;
        }

        Mail::to($to)->queue(new NewOrderMail($order->load('items')));
    }

    /**
     * Snapshots each cart line onto the order and decrements stock atomically,
     * flooring at zero so stock can never go negative.
     *
     * @param  Collection<int, CartItem>  $items
     */
    private function createItems(Order $order, Collection $items): void
    {
        $products = [];

        foreach ($items as $item) {
            $variant = $item->productVariant;
            $product = $variant->product;

            // Lock the row inside the transaction so concurrent checkouts of the
            // same variant serialise. If the locked stock can't cover this line,
            // reject the whole order (rolls back) rather than silently overselling
            // or flooring to a phantom sale — zero-stock variants aren't buyable.
            // This locked re-read is the real oversell backstop: the stepper caps
            // are UX only and can be stale by the time the customer checks out.
            $locked = ProductVariant::whereKey($variant->id)->lockForUpdate()->first();
            $name = $product->getTranslation('name', app()->getLocale());
            if ($locked === null || $locked->stock_qty < 1) {
                throw CheckoutException::outOfStock($name);
            }
            if ($locked->stock_qty < $item->qty) {
                throw CheckoutException::insufficientStock($name, (int) $item->qty, (int) $locked->stock_qty);
            }

            ProductVariant::whereKey($variant->id)
                ->update(['stock_qty' => DB::raw('stock_qty - '.(int) $item->qty)]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_name' => $product->getTranslations('name'),
                'variant_label' => $this->variantLabel($variant),
                'sku' => $variant->sku,
                'image_url' => $product->getFirstMediaUrl('gallery') ?: null,
                'unit_price' => (float) $variant->price,
                'qty' => $item->qty,
                'line_total' => round((float) $variant->price * $item->qty, 2),
            ]);

            $products[$product->id] = $product;
        }

        foreach ($products as $product) {
            $this->cache->handle($product);
        }
    }

    /** @return array<string, string> */
    private function variantLabel(ProductVariant $variant): array
    {
        $labels = [];
        foreach (['ar', 'en'] as $locale) {
            $labels[$locale] = $variant->attributeValues
                ->map(fn ($av) => $av->getTranslation('label', $locale))
                ->filter()
                ->implode(' · ');
        }

        return $labels;
    }

    private function holdHours(): int
    {
        return (int) $this->settings->get('commerce.instapay_hold_hours', 24);
    }

    private function cardWindowMinutes(): int
    {
        return (int) $this->settings->get('commerce.card_payment_window_minutes', 60);
    }
}
