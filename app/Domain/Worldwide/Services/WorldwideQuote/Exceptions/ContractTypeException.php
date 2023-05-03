<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Exceptions;

use JetBrains\PhpStorm\Pure;

class ContractTypeException extends \RuntimeException
{
    #[Pure]
    public static function unsupportedContractType(): static
    {
        return new static('Unsupported contract type provided.');
    }
}
