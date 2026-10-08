<?php

namespace App\Enums;

/** How an attribute's values render as a choice and as a filter. */
enum AttributeDisplay: string
{
    case Chip = 'chip';
    case Swatch = 'swatch';
    case Button = 'button';
}
