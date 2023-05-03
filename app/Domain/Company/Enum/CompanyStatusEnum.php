<?php

namespace App\Domain\Company\Enum;

use ArchTech\Enums\From;

enum CompanyStatusEnum: int
{
    use From;

    case Liquidation = 2;
    case Active = 1;
    case Dissolved = 0;
}
