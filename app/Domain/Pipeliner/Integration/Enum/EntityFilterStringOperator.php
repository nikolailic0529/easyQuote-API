<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum EntityFilterStringOperator
{
    case contains;
    case empty;
    case ends;
    case eq;
    case icontains;
    case iends;
    case ieq;
    case istarts;
    case null;
    case starts;
}
