<?php

namespace App\Services\WorldwideQuote\Exceptions;

use JetBrains\PhpStorm\Pure;
use RuntimeException;

class DiscountException extends RuntimeException
{
    #[Pure]
    public static function unsupportedEntityType(): static
    {
        return new static("Unsupported discount entity type provided.");
    }
}