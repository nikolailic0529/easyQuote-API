<?php

namespace App\Domain\Worldwide\Enum;

enum OpportunityStatus: int
{
    case LOST = 0;
    case NOT_LOST = 1;
}
