<?php

namespace App\Exceptions;

use RuntimeException;

/** Raised when an order cannot be placed (empty cart, item unavailable or short). */
class CheckoutException extends RuntimeException
{
    public string $itemName = '';

    /** '' | 'out_of_stock' | 'insufficient_qty' */
    public string $reason = '';

    public int $requested = 0;

    public int $available = 0;

    public static function emptyCart(): self
    {
        return new self('ui.checkout_cart_empty');
    }

    /** The item is completely unavailable (deleted, inactive or zero stock). */
    public static function outOfStock(string $name = ''): self
    {
        $e = new self('ui.checkout_item_unavailable');
        $e->itemName = $name;
        $e->reason = 'out_of_stock';

        return $e;
    }

    /** Some stock exists, but less than the quantity the customer asked for. */
    public static function insufficientStock(string $name, int $requested, int $available): self
    {
        $e = new self('ui.checkout_item_unavailable');
        $e->itemName = $name;
        $e->reason = 'insufficient_qty';
        $e->requested = $requested;
        $e->available = $available;

        return $e;
    }
}
