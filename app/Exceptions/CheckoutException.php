<?php

namespace App\Exceptions;

use RuntimeException;

/** Raised when an order cannot be placed (empty cart, item no longer available). */
class CheckoutException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('ui.checkout_cart_empty');
    }

    public static function unavailable(): self
    {
        return new self('ui.checkout_item_unavailable');
    }
}
