<?php

namespace App\Enums;

enum PaymentMethodCode: string
{
    case Cod = 'cod';
    case Card = 'card';
    case InstapayWhatsapp = 'instapay_whatsapp';
}
