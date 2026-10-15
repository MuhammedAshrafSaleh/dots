<?php

namespace App\Actions;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Cancels InstaPay orders whose transfer window (reserved_until) has elapsed
 * without payment, restores the reserved stock and writes a history note.
 * Idempotent: a released order drops out of the query (reservation cleared and
 * status moved off "new"), so re-running never double-restores stock.
 */
class ReleaseExpiredReservations
{
    public function __construct(private readonly UpdateProductCacheColumns $cache) {}

    /** @return int number of orders released */
    public function handle(): int
    {
        $orders = Order::query()
            ->where('payment_status', OrderPaymentStatus::AwaitingTransfer)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<=', now())
            ->with('items')
            ->get();

        foreach ($orders as $order) {
            $this->release($order);
        }

        return $orders->count();
    }

    private function release(Order $order): void
    {
        DB::transaction(function () use ($order): void {
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

            $order->statusHistories()->create([
                'status' => OrderStatus::Cancelled->value,
                'note' => [
                    'en' => 'InstaPay transfer window expired — order cancelled automatically and reserved stock released.',
                    'ar' => 'انتهت مهلة تحويل إنستاباي — أُلغي الطلب تلقائيًا وأُعيد المخزون المحجوز.',
                ],
                'created_at' => now(),
            ]);

            foreach ($productIds as $id) {
                $product = Product::find($id);
                if ($product !== null) {
                    $this->cache->handle($product);
                }
            }
        });
    }
}
