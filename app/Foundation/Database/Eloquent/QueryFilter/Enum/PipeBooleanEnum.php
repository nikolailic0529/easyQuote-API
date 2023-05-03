<?php

namespace App\Foundation\Database\Eloquent\QueryFilter\Enum;

enum PipeBooleanEnum: string
{
    case Or = 'or';
    case And = 'and';
}
