<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum PriorityEnum: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
}
