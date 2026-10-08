<?php

namespace App\Enums;

/**
 * Cached stock state computed from a product's variants.
 * `in` = available, `low` = at or below threshold, `order` = made to order / out.
 */
enum StockStatus: string
{
    case In = 'in';
    case Low = 'low';
    case Order = 'order';
}
