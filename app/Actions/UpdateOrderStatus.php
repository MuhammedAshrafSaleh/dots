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
 * when the order moves into "cancelled" from any live state, restores the
 * reserved stock exactly once (mirrors ReleaseExpiredReservations).
 */
class UpdateOrderStatus
{
    public function __construct(private readonly UpdateProductCacheColumns $cache) {}

    /**
     * @param  array<string, string>  $customerNote  translatable ['ar' => .., 'en' => ..]
     */
    public function handle(
        Order $order,
        OrderStatus $status,
        OrderPaymentStatus $paymentStatus,
        ?string $expectedDeliveryAt,
        array $customerNote,
        ?string $internalNote,
        ?int $userId,
    ): void {
        DB::transaction(function () use ($order, $status, $paymentStatus, $expectedDeliveryAt, $customerNote, $internalNote, $userId): void {
            $cancelling = $status === OrderStatus::Cancelled && $order->status !== OrderStatus::Cancelled;

            if ($cancelling) {
                $this->restoreStock($order);
            }

            $order->status = $status;
            $order->payment_status = $paymentStatus;
            if ($expectedDeliveryAt !== null && $expectedDeliveryAt !== '') {
                $order->expected_delivery_at = Carbon::parse($expectedDeliveryAt);
            }
            if ($cancelling) {
                $order->reserved_until = null;
            }
            $order->save();

            $note = array_filter($customerNote, fn (string $v): bool => trim($v) !== '');

            $order->statusHistories()->create([
                'status' => $status->value,
                'note' => $note === [] ? null : $note,
                'internal_note' => ($internalNote !== null && trim($internalNote) !== '') ? $internalNote : null,
                'user_id' => $userId,
                'created_at' => now(),
            ]);
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

        foreach ($productIds as $id) {
            $product = Product::find($id);
            if ($product !== null) {
                $this->cache->handle($product);
            }
        }
    }
}
