<?php

namespace App\Enum;

use ArchTech\Enums\From;

enum DataAllocationStageEnum: int
{
    use From;

    case Init = 1;
    case Import = 30;
    case Review = 60;
    case Results = 100;
}
