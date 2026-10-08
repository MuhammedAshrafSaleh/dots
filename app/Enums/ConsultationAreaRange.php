<?php

namespace App\Enums;

/** Approximate project area in m² (case names cannot start with a digit). */
enum ConsultationAreaRange: string
{
    case Lt150 = 'lt150';
    case R150To400 = '150_400';
    case R400To1000 = '400_1000';
    case Gt1000 = 'gt1000';
}
