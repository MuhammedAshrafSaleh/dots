<?php

namespace App\Enums;

/** Where a WhatsApp quote request originated. */
enum QuoteSource: string
{
    case Product = 'product';
    case Cart = 'cart';
    case InstapayOrder = 'instapay_order';
    case Boq = 'boq';
    case Brand = 'brand';
    case Service = 'service';
    case Project = 'project';
    case Article = 'article';
    case OrderHelp = 'order_help';
    case General = 'general';
}
