<?php

namespace App\Domain\Margin\Enum;

enum MarginQuoteTypeEnum: string
{
    case New = 'New';
    case Renewal = 'Renewal';
}
