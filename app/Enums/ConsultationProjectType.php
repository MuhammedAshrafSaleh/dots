<?php

namespace App\Enums;

enum ConsultationProjectType: string
{
    case Apartment = 'apartment';
    case Villa = 'villa';
    case Office = 'office';
    case RetailFnb = 'retail_fnb';
    case Hotel = 'hotel';
}
