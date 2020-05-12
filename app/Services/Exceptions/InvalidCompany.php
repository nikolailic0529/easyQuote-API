<?php

namespace App\Services\Exceptions;

use InvalidArgumentException;

class InvalidCompany extends InvalidArgumentException
{
    public static function nonInternal()
    {
        throw new static('Provided company must be of Internal type.');
    }
}