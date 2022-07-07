<?php

namespace App\Integrations\Pipeliner\Enum;

enum OpportunityLabelFlag: int
{
    case Priority = 1;
    case Hot = 2;
    case Stalled = 4;
}