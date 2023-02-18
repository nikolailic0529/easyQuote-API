<?php

namespace App\Domain\Rescue\Exceptions;

class EqCustomer extends \InvalidArgumentException
{
    public static function nonEqCustomer()
    {
        throw new static('Non EQ Customer provided!');
    }
}
