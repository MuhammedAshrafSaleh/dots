<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use Illuminate\Support\Carbon;

/**
 * Validates coupon codes and computes their discount. Pure (no session) so the
 * rules — min total, dates, usage limit, once-per-customer — are directly testable.
 */
class Coupons
{
    public function resolve(string $code, float $subtotal, ?int $customerId = null): CouponResult
    {
        $code = trim($code);
        if ($code === '') {
            return CouponResult::fail('ui.coupon_invalid');
        }

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [mb_strtoupper($code)])->first();
        if ($coupon === null || ! $coupon->is_active) {
            return CouponResult::fail('ui.coupon_invalid');
        }

        $now = Carbon::now();
        if ($coupon->starts_at !== null && $coupon->starts_at->greaterThan($now)) {
            return CouponResult::fail('ui.coupon_invalid');
        }
        if ($coupon->ends_at !== null && $coupon->ends_at->lessThan($now)) {
            return CouponResult::fail('ui.coupon_expired');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return CouponResult::fail('ui.coupon_limit');
        }

        $min = (float) $coupon->min_order_total;
        if ($min > 0 && $subtotal < $min) {
            return CouponResult::fail('ui.coupon_min', ['amount' => number_format($min, 0)]);
        }

        if ($coupon->once_per_customer && $customerId !== null) {
            $alreadyUsed = CouponRedemption::where('coupon_id', $coupon->id)
                ->where('customer_id', $customerId)
                ->exists();
            if ($alreadyUsed) {
                return CouponResult::fail('ui.coupon_used');
            }
        }

        return CouponResult::ok($coupon, $this->discountFor($coupon, $subtotal));
    }

    /** Discount amount for a coupon against a subtotal, capped at the subtotal. */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $value = (float) $coupon->value;

        $discount = $coupon->type === CouponType::Percent
            ? $subtotal * $value / 100
            : $value;

        return round(min($subtotal, max(0.0, $discount)), 2);
    }
}
