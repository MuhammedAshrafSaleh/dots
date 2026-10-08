<?php

namespace App\Enums;

/** Project category used by the projects-page type filter. */
enum ProjectType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Outdoor = 'outdoor';
    case SmartHome = 'smart_home';
    case Hospitality = 'hospitality';
}
