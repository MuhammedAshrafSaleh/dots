<?php

namespace App\Enums;

/**
 * Relationship kind between two products:
 * `together` = "frequently bought together", `cart_suggestion` = "don't forget" in the cart.
 */
enum ProductRelationType: string
{
    case Together = 'together';
    case CartSuggestion = 'cart_suggestion';
}
