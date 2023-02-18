<?php

namespace App\Domain\ExchangeRate\Enum;

enum EventFrequencyEnum: string
{
    case EveryMinute = 'everyMinute';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case TwiceDaily = 'twiceDaily';
    case Weekdays = 'weekdays';
    case Weekends = 'weekends';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
