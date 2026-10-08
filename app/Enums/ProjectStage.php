<?php

namespace App\Enums;

/** Stage shown on a current project's progress bar. */
enum ProjectStage: string
{
    case Design = 'design';
    case FirstFix = 'first_fix';
    case Installation = 'installation';
    case Commissioning = 'commissioning';
    case Handover = 'handover';
}
