<?php

namespace App\Enums;

/** Shared lifecycle for quote and consultation requests. */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Converted = 'converted';
    case Closed = 'closed';
}
