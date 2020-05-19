<?php

namespace App\Services\Exceptions;

use InvalidArgumentException;

class ServiceLookupRoute extends InvalidArgumentException
{
    /**
     * Throw invalid route exception.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public static function invalidName()
    {
        throw new static('Undefined service route name');
    }
}