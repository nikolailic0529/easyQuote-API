<?php

namespace App\Foundation\Validation\Rules\Enum;

enum CountOption
{
    case NotSet;
    case Exactly;
    case Min;
    case Max;
}
