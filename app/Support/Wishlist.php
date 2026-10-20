<?php

namespace App\Support;

use App\Models\Customer;

/**
 * Request-scoped cache of the signed-in customer's saved product ids, so every
 * product card on a page can answer "is this saved?" from a single query.
 * Bound as a singleton (one instance per request).
 */
class Wishlist
{
    /** @var array<int, int>|null */
    private ?array $ids = null;

    /** @return array<int, int> */
    public function savedProductIds(): array
    {
        if ($this->ids !== null) {
            return $this->ids;
        }

        /** @var Customer|null $customer */
        $customer = auth('customer')->user();

        return $this->ids = $customer !== null
            ? $customer->wishlist()->pluck('products.id')->all()
            : [];
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->savedProductIds(), true);
    }

    /** Drop the cache after a toggle so a later read in the same request is fresh. */
    public function forget(): void
    {
        $this->ids = null;
    }
}
