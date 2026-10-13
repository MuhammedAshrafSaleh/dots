<?php

namespace App\Enums;

use Illuminate\Support\Str;

/** Project category used by the projects-page type filter. */
enum ProjectType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Outdoor = 'outdoor';
    case SmartHome = 'smart_home';
    case Hospitality = 'hospitality';

    public function label(): string
    {
        return __('ui.type_'.str_replace('-', '_', Str::snake($this->name)));
    }
}
