<?php

namespace App\Support;

/** Immutable snapshot of a cart's computed money lines (all VAT-inclusive). */
class CartTotals
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $deliveryFee,
        public readonly bool $deliveryFree,
        public readonly ?float $freeShipThreshold,
        public readonly float $total,
        public readonly float $vat,
        public readonly float $vatRate,
        public readonly int $itemCount,
        public readonly ?string $couponCode,
    ) {}

    /** Amount still needed to unlock free delivery (0 when none / already met). */
    public function freeShipRemaining(): float
    {
        if ($this->freeShipThreshold === null) {
            return 0.0;
        }

        return round(max(0.0, $this->freeShipThreshold - $this->subtotal), 2);
    }
}
