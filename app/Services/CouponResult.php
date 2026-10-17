<?php

namespace App\Services;

use App\Models\Coupon;

/** Outcome of validating a coupon code against a cart subtotal. */
class CouponResult
{
    /** @param  array<string, mixed>  $errorParams */
    private function __construct(
        public readonly ?Coupon $coupon,
        public readonly ?string $error,
        public readonly array $errorParams,
        public readonly float $discount,
    ) {}

    /** @param  array<string, mixed>  $params */
    public static function fail(string $errorKey, array $params = []): self
    {
        return new self(null, $errorKey, $params, 0.0);
    }

    public static function ok(Coupon $coupon, float $discount): self
    {
        return new self($coupon, null, [], $discount);
    }
}
