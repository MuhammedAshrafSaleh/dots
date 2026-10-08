<?php

namespace App\Enums;

/** Payment state tracked on the order (distinct from a gateway PaymentStatus). */
enum OrderPaymentStatus: string
{
    case Pending = 'pending';
    case AwaitingTransfer = 'awaiting_transfer';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
