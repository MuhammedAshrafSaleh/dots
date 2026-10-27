<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Rolls back an order's coupon usage when the order is cancelled (auto-sweep or
 * admin). Redemption rows are soft-released — `released_at` is stamped, the row
 * is kept so the history of a coupon used on a later-cancelled order survives —
 * and the coupon's `used_count` counter is given back by the number of rows
 * actually released (floored at 0). Idempotent: already-released rows are
 * skipped, so a re-run releases nothing and never double-decrements.
 *
 * Call inside the caller's locked transaction. An order with no coupon is a
 * no-op.
 */
class ReleaseOrderCoupon
{
    public function handle(Order $order): void
    {
        $rows = CouponRedemption::where('order_id', $order->id)
            ->whereNull('released_at')
            ->get(['id', 'coupon_id']);

        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows->groupBy('coupon_id') as $couponId => $group) {
            Coupon::whereKey($couponId)
                ->update(['used_count' => DB::raw('GREATEST(used_count - '.$group->count().', 0)')]);
        }

        CouponRedemption::whereIn('id', $rows->pluck('id'))
            ->update(['released_at' => now()]);
    }
}
