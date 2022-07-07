<?php

namespace App\Integrations\Pipeliner\Enum;

enum PriorityEnum: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
}