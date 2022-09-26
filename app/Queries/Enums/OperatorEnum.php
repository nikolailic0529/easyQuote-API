<?php

namespace App\Queries\Enums;

enum OperatorEnum: string
{
    case Eq = '=';
    case Ne = '!=';
    case Gt = '>';
    case Gte = '>=';
    case Lt = '<';
    case Lte = '<=';
    case In = 'in';
    case NotIn = 'not in';
    case Like = 'like';
}
