<?php

namespace App\Rules\Enum;

enum CountOption
{
    case NotSet;
    case Exactly;
    case Min;
    case Max;
}
