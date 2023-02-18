<?php

namespace App\Domain\Settings\Enum;

enum PriceCalculationDurationEnum: string
{
    case Day = 'day';
    case Month = 'month';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
