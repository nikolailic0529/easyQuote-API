<?php

namespace App\Domain\Priority\Enum;

enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}
