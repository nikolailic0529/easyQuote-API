<?php

namespace App\Queries\Enums;

enum PipeBooleanEnum: string
{
    case Or = 'or';
    case And = 'and';
}
