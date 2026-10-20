<?php

namespace App\Services;

use App\Enums\ProductRelationType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CartTotals;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the current cart (customer when authenticated, else guest session),
 * mutates its lines and computes totals. Coupon selection lives in the session.
 */
class CartManager
{
    private const COUPON_KEY = 'cart.coupon';

    public function __construct(private readonly Settings $settings) {}

    public function customerId(): ?int
    {
        $id = Auth::guard('customer')->id();

        return $id !== null ? (int) $id : null;
    }

    public function currentOrNull(): ?Cart
    {
        $customerId = $this->customerId();
        if ($customerId !== null) {
            return Cart::where('customer_id', $customerId)->first();
        }

        return Cart::whereNull('customer_id')
            ->where('session_id', session()->getId())
            ->first();
    }

    public function current(): Cart
    {
        $customerId = $this->customerId();
        if ($customerId !== null) {
            return Cart::firstOrCreate(['customer_id' => $customerId]);
        }

        return Cart::firstOrCreate(['customer_id' => null, 'session_id' => session()->getId()]);
    }

    /** @return Collection<int, CartItem> */
    public function items(): Collection
    {
        $cart = $this->currentOrNull();
        if ($cart === null) {
            return new Collection;
        }

        return $cart->items()
            ->with(['productVariant.product.media', 'productVariant.attributeValues'])
            ->get()
            ->filter(fn (CartItem $item) => $item->productVariant !== null && $item->productVariant->product !== null)
            ->values();
    }

    public function add(int $variantId, int $qty = 1): void
    {
        $variant = ProductVariant::active()->whereNotNull('price')->find($variantId);
        if ($variant === null) {
            return;
        }

        $qty = max(1, min(99, $qty));
        $cart = $this->current();

        $item = $cart->items()->firstOrNew(['product_variant_id' => $variantId]);
        $existing = $item->exists ? $item->qty : 0;
        $item->qty = min(99, $existing + $qty);
        $item->save();
    }

    public function updateQty(int $itemId, int $qty): void
    {
        $cart = $this->currentOrNull();
        $item = $cart?->items()->find($itemId);
        if ($item === null) {
            return;
        }

        if ($qty < 1) {
            $item->delete();

            return;
        }

        $item->qty = min(99, $qty);
        $item->save();
    }

    public function remove(int $itemId): void
    {
        $this->currentOrNull()?->items()->where('id', $itemId)->delete();
    }

    public function itemCount(): int
    {
        $cart = $this->currentOrNull();

        return $cart === null ? 0 : (int) $cart->items()->sum('qty');
    }

    public function subtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->items() as $item) {
            $subtotal += round((float) ($item->productVariant->price ?? 0) * $item->qty, 2);
        }

        return round($subtotal, 2);
    }

    public function applyCoupon(string $code): CouponResult
    {
        $result = app(Coupons::class)->resolve($code, $this->subtotal(), $this->customerId());
        if ($result->error === null && $result->coupon !== null) {
            session([self::COUPON_KEY => $result->coupon->code]);
        }

        return $result;
    }

    public function removeCoupon(): void
    {
        session()->forget(self::COUPON_KEY);
    }

    public function totals(): CartTotals
    {
        [$fee, $free, $threshold] = $this->deliveryEstimate($this->subtotal());

        return $this->buildTotals($fee, $free, $threshold);
    }

    /**
     * Totals for a specific delivery method (its own fee + free-over rule),
     * used on the checkout page where the customer picks the method.
     */
    public function totalsForDelivery(?DeliveryMethod $method): CartTotals
    {
        $subtotal = $this->subtotal();

        if ($method === null) {
            return $this->buildTotals(0.0, false, null);
        }

        $threshold = $method->free_over_amount !== null ? (float) $method->free_over_amount : null;
        $free = $threshold !== null && $subtotal >= $threshold;

        return $this->buildTotals($free ? 0.0 : (float) $method->price, $free, $threshold);
    }

    /** Builds the money snapshot from the current items + session coupon. */
    private function buildTotals(float $deliveryFee, bool $deliveryFree, ?float $freeThreshold): CartTotals
    {
        $subtotal = 0.0;
        $count = 0;
        foreach ($this->items() as $item) {
            $subtotal += round((float) ($item->productVariant->price ?? 0) * $item->qty, 2);
            $count += $item->qty;
        }
        $subtotal = round($subtotal, 2);

        [$discount, $couponCode] = $this->sessionDiscount($subtotal);

        $total = round($subtotal - $discount + $deliveryFee, 2);
        $vatRate = (float) $this->settings->get('commerce.vat_rate', 14);
        $vat = $total > 0 ? round($total * $vatRate / (100 + $vatRate), 2) : 0.0;

        return new CartTotals(
            subtotal: $subtotal,
            discount: $discount,
            deliveryFee: $deliveryFee,
            deliveryFree: $deliveryFree,
            freeShipThreshold: $freeThreshold,
            total: $total,
            vat: $vat,
            vatRate: $vatRate,
            itemCount: $count,
            couponCode: $couponCode,
        );
    }

    /**
     * Resolves the session coupon against a subtotal. Drops it from the session
     * when it is no longer valid (e.g. items removed below the minimum).
     *
     * @return array{0: float, 1: string|null}
     */
    private function sessionDiscount(float $subtotal): array
    {
        $code = session(self::COUPON_KEY);
        if (! is_string($code) || $code === '') {
            return [0.0, null];
        }

        $result = app(Coupons::class)->resolve($code, $subtotal, $this->customerId());
        if ($result->error !== null) {
            session()->forget(self::COUPON_KEY);

            return [0.0, null];
        }

        if ($result->coupon !== null) {
            return [$result->discount, $result->coupon->code];
        }

        return [0.0, null];
    }

    /** The coupon currently held in the session, if still valid for the subtotal. */
    public function activeCoupon(): ?Coupon
    {
        $code = session(self::COUPON_KEY);
        if (! is_string($code) || $code === '') {
            return null;
        }

        $result = app(Coupons::class)->resolve($code, $this->subtotal(), $this->customerId());

        return $result->error === null ? $result->coupon : null;
    }

    /**
     * Active delivery methods, keeping address-based methods only when they
     * serve the given governorate (empty pivot = everywhere). Pickup methods
     * (no address) are always available.
     *
     * @return Collection<int, DeliveryMethod>
     */
    public function availableDeliveryMethods(?int $governorateId = null): Collection
    {
        return DeliveryMethod::active()
            ->with('governorates:id')
            ->orderBy('sort_order')
            ->get()
            ->filter(function (DeliveryMethod $method) use ($governorateId): bool {
                if (! $method->requires_address) {
                    return true;
                }

                $served = $method->governorates;
                if ($served->isEmpty()) {
                    return true;
                }

                return $governorateId !== null && $served->contains('id', $governorateId);
            })
            ->values();
    }

    /**
     * Cheapest active address-based delivery method as a pre-checkout estimate.
     *
     * @return array{0: float, 1: bool, 2: float|null}
     */
    private function deliveryEstimate(float $subtotal): array
    {
        $method = DeliveryMethod::active()
            ->where('requires_address', true)
            ->where('price_is_from', false)
            ->orderBy('sort_order')
            ->first();

        if ($method === null) {
            return [0.0, false, null];
        }

        $threshold = $method->free_over_amount !== null ? (float) $method->free_over_amount : null;
        $free = $threshold !== null && $subtotal >= $threshold;

        return [$free ? 0.0 : (float) $method->price, $free, $threshold];
    }

    public function clear(): void
    {
        $this->currentOrNull()?->items()->delete();
        $this->removeCoupon();
    }

    /**
     * Folds the guest session cart into the logged-in customer's cart (PRD §9).
     * Pass the session id captured BEFORE authentication: logging a customer in
     * migrates the session id, so by the time this runs the guest cart would no
     * longer be found by the current id. Quantities for the same variant add up
     * (capped at 99); the emptied guest cart is removed.
     */
    public function mergeGuestCartOnLogin(?string $guestSessionId = null): void
    {
        $customerId = $this->customerId();
        if ($customerId === null) {
            return;
        }

        $guest = Cart::whereNull('customer_id')
            ->where('session_id', $guestSessionId ?? session()->getId())
            ->first();
        if ($guest === null) {
            return;
        }

        $customerCart = Cart::firstOrCreate(['customer_id' => $customerId]);
        if ($guest->id === $customerCart->id) {
            return;
        }

        foreach ($guest->items as $guestItem) {
            $item = $customerCart->items()->firstOrNew(['product_variant_id' => $guestItem->product_variant_id]);
            $existing = $item->exists ? $item->qty : 0;
            $item->qty = min(99, $existing + $guestItem->qty);
            $item->save();
        }

        $guest->delete();
    }

    /** @return Collection<int, Product> */
    public function suggestions(int $limit = 4): Collection
    {
        $productIds = $this->items()
            ->map(fn (CartItem $item) => $item->productVariant->product->id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($productIds)) {
            return new Collection;
        }

        $relatedIds = DB::table('product_relations')
            ->whereIn('product_id', $productIds)
            ->where('type', ProductRelationType::CartSuggestion->value)
            ->orderBy('sort_order')
            ->pluck('related_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => in_array($id, $productIds, true))
            ->take($limit)
            ->all();

        if (empty($relatedIds)) {
            return new Collection;
        }

        return Product::query()
            ->published()
            ->whereIn('id', $relatedIds)
            ->with(['brand', 'variants.swatchAttributeValues', 'media'])
            ->get();
    }

    public function whatsappItemsText(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->items()
            ->map(function (CartItem $item) use ($locale): string {
                $name = $item->productVariant->product->getTranslation('name', $locale);
                $sku = $item->productVariant->sku;

                return '• '.$name.' ('.$sku.') ×'.$item->qty;
            })
            ->implode("\n");
    }
}
