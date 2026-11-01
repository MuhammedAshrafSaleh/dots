<?php

namespace App\Actions;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Applies an admin status/payment update to an order inside one transaction:
 * records a single history row (customer-visible note + internal note), and
 * keeps stock + coupon symmetric across the cancel boundary. Moving an order
 * INTO "cancelled" from any live state restores the reserved stock and releases
 * the coupon (mirrors ReleaseExpiredReservations); moving it back OUT of
 * "cancelled" re-reserves the stock (clamped at zero, never negative) and
 * re-consumes the coupon. The order row is locked for the whole update so two
 * admins acting at once can't both cross the same boundary.
 */
class UpdateOrderStatus
{
    public function __construct(
        private readonly UpdateProductCacheColumns $cache,
        private readonly ReleaseOrderCoupon $releaseCoupon,
        private readonly ConsumeOrderCoupon $consumeCoupon,
    ) {}

    /**
     * Returns the stock shortages found when reinstating (empty on every other
     * path), so the caller can surface them to the admin.
     *
     * @param  array<string, string>  $customerNote  translatable ['ar' => .., 'en' => ..]
     * @return list<array{label: string, needed: int, available: int}>
     */
    public function handle(
        Order $order,
        OrderStatus $status,
        OrderPaymentStatus $paymentStatus,
        ?string $expectedDeliveryAt,
        array $customerNote,
        ?string $internalNote,
        ?int $userId,
    ): array {
        return DB::transaction(function () use ($order, $status, $paymentStatus, $expectedDeliveryAt, $customerNote, $internalNote, $userId): array {
            // One lock for the whole update: re-read the order's authoritative
            // status under the lock so two admins saving at once can't both cross
            // the same boundary (double stock restore on concurrent cancel, or
            // double decrement on concurrent reinstate). The same lock serves
            // both directions — no second mechanism.
            $current = (Order::whereKey($order->id)->lockForUpdate()->first() ?? $order)->status;

            $cancelling = $status === OrderStatus::Cancelled && $current !== OrderStatus::Cancelled;
            $reinstating = $current === OrderStatus::Cancelled && $status !== OrderStatus::Cancelled;

            $shortages = [];

            if ($cancelling) {
                $this->restoreStock($order);
                $this->releaseCoupon->handle($order);
            } elseif ($reinstating) {
                $shortages = $this->reserveStock($order);
                $this->consumeCoupon->handle($order);
            }

            $order->status = $status;
            $order->payment_status = $paymentStatus;
            if ($expectedDeliveryAt !== null && $expectedDeliveryAt !== '') {
                $order->expected_delivery_at = Carbon::parse($expectedDeliveryAt);
            }
            if ($cancelling) {
                $order->reserved_until = null;
            }
            // Reinstating leaves reserved_until null: the admin now manages this
            // order's stock (like COD), so the sweeper must never touch it.
            $order->save();

            $note = array_filter($customerNote, fn (string $v): bool => trim($v) !== '');

            $internal = ($internalNote !== null && trim($internalNote) !== '') ? trim($internalNote) : null;
            if ($shortages !== []) {
                // Persist the shortage on the order (staff-only internal note) so
                // it stays visible after the toast is gone — the signal to call
                // the customer. We never auto-message the customer.
                $internal = trim(($internal !== null ? $internal."\n" : '').$this->shortageNote($shortages));
            }

            $order->statusHistories()->create([
                'status' => $status->value,
                'note' => $note === [] ? null : $note,
                'internal_note' => $internal,
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $shortages;
        });
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('items');
        $productIds = [];

        foreach ($order->items as $item) {
            if ($item->product_variant_id !== null) {
                ProductVariant::whereKey($item->product_variant_id)
                    ->update(['stock_qty' => DB::raw('stock_qty + '.(int) $item->qty)]);
            }
            if ($item->product_id !== null) {
                $productIds[$item->product_id] = $item->product_id;
            }
        }

        $this->refreshCache($productIds);
    }

    /**
     * Re-decrements each line's stock when an order is reinstated out of
     * cancelled, clamped at zero so stock never goes negative. Any line whose
     * current stock cannot cover the ordered qty is returned as a shortage.
     *
     * @return list<array{label: string, needed: int, available: int}>
     */
    private function reserveStock(Order $order): array
    {
        $order->loadMissing('items');
        $productIds = [];
        $shortages = [];

        foreach ($order->items as $item) {
            if ($item->product_variant_id !== null) {
                // Lock the variant row (as PlaceOrder does) so the read-clamp-write
                // can't race a concurrent checkout of the same variant.
                $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first();
                if ($variant !== null) {
                    $available = (int) $variant->stock_qty;
                    $needed = (int) $item->qty;
                    $take = min($available, $needed);

                    if ($take > 0) {
                        ProductVariant::whereKey($variant->id)
                            ->update(['stock_qty' => DB::raw('stock_qty - '.$take)]);
                    }
                    if ($take < $needed) {
                        $shortages[] = [
                            'label' => $item->getTranslation('product_name', app()->getLocale()),
                            'needed' => $needed,
                            'available' => $available,
                        ];
                    }
                }
            }
            if ($item->product_id !== null) {
                $productIds[$item->product_id] = $item->product_id;
            }
        }

        $this->refreshCache($productIds);

        return $shortages;
    }

    /** @param  array<int, int>  $productIds */
    private function refreshCache(array $productIds): void
    {
        foreach ($productIds as $id) {
            $product = Product::find($id);
            if ($product !== null) {
                $this->cache->handle($product);
            }
        }
    }

    /**
     * @param  list<array{label: string, needed: int, available: int}>  $shortages
     */
    private function shortageNote(array $shortages): string
    {
        $lines = array_map(
            fn (array $s): string => __('admin.orders.reinstate_short_line', [
                'product' => $s['label'],
                'needed' => (string) $s['needed'],
                'available' => (string) $s['available'],
            ]),
            $shortages,
        );

        return __('admin.orders.reinstate_short_prefix').' '.implode('; ', $lines);
    }
}
