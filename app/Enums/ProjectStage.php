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

    public function label(): string
    {
        return __('ui.stage_'.$this->value);
    }

    public function index(): int
    {
        return array_search($this, self::cases(), true);
    }
}
