<?php

namespace App\Actions;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Cancels orders whose pay-by deadline (reserved_until) has elapsed without
 * payment, restores the reserved stock, rolls back any coupon use and writes a
 * reason-aware history note. Covers InstaPay (transfer window) and card
 * (abandoned / declined, pending or failed) orders; COD holds carry no deadline
 * (reserved_until NULL) and are never swept.
 *
 * Each order is cancelled inside its own transaction with the row locked and
 * its eligibility re-checked under the lock, so an in-flight webhook that
 * settles the order first wins the race and the sweep no-ops. Idempotent: a
 * released order drops out of the query (reservation cleared, status
 * cancelled), so re-running never double-restores stock or double-refunds a
 * coupon.
 */
class ReleaseExpiredReservations
{
    /** Order payment states whose unpaid stock the sweeper returns. */
    private const RELEASABLE = [
        OrderPaymentStatus::AwaitingTransfer,
        OrderPaymentStatus::Pending,
        OrderPaymentStatus::Failed,
    ];

    public function __construct(
        private readonly UpdateProductCacheColumns $cache,
        private readonly ReleaseOrderCoupon $coupon,
    ) {}

    /** @return int number of orders released */
    public function handle(): int
    {
        $ids = Order::query()
            ->whereIn('payment_status', array_map(fn (OrderPaymentStatus $s) => $s->value, self::RELEASABLE))
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<=', now())
            ->pluck('id');

        $released = 0;
        foreach ($ids as $id) {
            if ($this->release((int) $id)) {
                $released++;
            }
        }

        return $released;
    }

    private function release(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId): bool {
            $order = Order::with('items')->lockForUpdate()->find($orderId);

            // Re-check eligibility under the lock: a webhook/customer action may
            // have settled or already cancelled the order since we listed ids.
            if ($order === null
                || $order->status === OrderStatus::Cancelled
                || $order->reserved_until === null
                || $order->reserved_until->greaterThan(now())
                || ! in_array($order->payment_status, self::RELEASABLE, true)) {
                return false;
            }

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

            $order->update([
                'status' => OrderStatus::Cancelled,
                'reserved_until' => null,
            ]);

            $this->coupon->handle($order);

            $order->statusHistories()->create([
                'status' => OrderStatus::Cancelled->value,
                'note' => $this->note($order->payment_method_code),
                'created_at' => now(),
            ]);

            foreach ($productIds as $id) {
                $product = Product::find($id);
                if ($product !== null) {
                    $this->cache->handle($product);
                }
            }

            return true;
        });
    }

    /** @return array<string, string> */
    private function note(?string $paymentMethodCode): array
    {
        if ($paymentMethodCode === PaymentMethodCode::Card->value) {
            return [
                'en' => 'Card payment not completed in time — order cancelled and reserved stock released.',
                'ar' => 'لم يكتمل الدفع بالبطاقة في الوقت المحدد — أُلغي الطلب وأُعيد المخزون المحجوز.',
            ];
        }

        return [
            'en' => 'InstaPay transfer window expired — order cancelled automatically and reserved stock released.',
            'ar' => 'انتهت مهلة تحويل إنستاباي — أُلغي الطلب تلقائيًا وأُعيد المخزون المحجوز.',
        ];
    }
}
