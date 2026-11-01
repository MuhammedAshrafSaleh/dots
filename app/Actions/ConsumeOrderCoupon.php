<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Re-consumes an order's coupon when the order is reinstated out of Cancelled —
 * the exact reverse of ReleaseOrderCoupon. Redemption rows this order
 * previously soft-released (`released_at` set) are re-activated
 * (`released_at = null`) and the coupon's `used_count` counter is given back by
 * the number of rows re-activated. Idempotent: a re-run finds no released rows,
 * re-activates nothing and never double-counts.
 *
 * Call inside the caller's locked transaction. An order with no released coupon
 * redemption is a no-op.
 */
class ConsumeOrderCoupon
{
    public function handle(Order $order): void
    {
        $rows = CouponRedemption::where('order_id', $order->id)
            ->whereNotNull('released_at')
            ->get(['id', 'coupon_id']);

        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows->groupBy('coupon_id') as $couponId => $group) {
            Coupon::whereKey($couponId)
                ->update(['used_count' => DB::raw('used_count + '.$group->count())]);
        }

        CouponRedemption::whereIn('id', $rows->pluck('id'))
            ->update(['released_at' => null]);
    }
}
