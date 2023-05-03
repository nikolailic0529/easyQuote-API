<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Exceptions;

use JetBrains\PhpStorm\Pure;

class DiscountException extends \RuntimeException
{
    #[Pure]
    public static function unsupportedEntityType(): static
    {
        return new static('Unsupported discount entity type provided.');
    }
}
