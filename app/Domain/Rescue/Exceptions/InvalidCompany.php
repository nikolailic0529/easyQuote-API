<?php

namespace App\Domain\Rescue\Exceptions;

class InvalidCompany extends \InvalidArgumentException
{
    public static function nonInternal()
    {
        return new static('Provided company must be of Internal type.');
    }
}
