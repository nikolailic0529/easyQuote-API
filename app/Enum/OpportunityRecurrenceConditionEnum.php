<?php

namespace App\Enum;

enum OpportunityRecurrenceConditionEnum: int
{
    case Won = 1 << 0;
    case Lost = 1 << 1;
}