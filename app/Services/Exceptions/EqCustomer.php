<?php

namespace App\Services\Exceptions;

use InvalidArgumentException;

class EqCustomer extends InvalidArgumentException
{
    public static function nonEqCustomer()
    {
        throw new static('Non EQ Customer provided!');
    }
}