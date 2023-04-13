<?php

namespace App\Domain\Authorization\Enum;

enum AccessEntityDirection: string
{
    case Owned = 'owned';
    case CurrentUnits = 'current_units';
    case All = 'all';
}
