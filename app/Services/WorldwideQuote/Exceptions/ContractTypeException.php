<?php

namespace App\Services\WorldwideQuote\Exceptions;

use JetBrains\PhpStorm\Pure;
use RuntimeException;

class ContractTypeException extends RuntimeException
{
    #[Pure]
    public static function unsupportedContractType(): static
    {
        return new static('Unsupported contract type provided.');
    }
}