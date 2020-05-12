<?php

namespace App\Repositories\Exceptions;

use InvalidArgumentException;

class InvalidModel extends InvalidArgumentException
{
    public static function key(string $class, string $func, $given)
    {
        return new static(sprintf("Argument passed to %s as key must be a valid model key or an instance of %s, %s given", $func, $class, gettype($given)));
    }
}