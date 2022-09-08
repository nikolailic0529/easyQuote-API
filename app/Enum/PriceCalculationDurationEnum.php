<?php

namespace App\Enum;

enum PriceCalculationDurationEnum: string
{
    case Day = 'day';
    case Month = 'month';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
