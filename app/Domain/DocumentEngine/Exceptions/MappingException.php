<?php

namespace App\Domain\DocumentEngine\Exceptions;

use JetBrains\PhpStorm\Pure;

class MappingException extends \Exception implements DocumentEngineClientException
{
    #[Pure]
    public static function headerNotFound(string $reference): static
    {
        return new static("No header with reference '$reference' found.");
    }

    #[Pure]
    public static function systemEntityConstraintsFailed(string $reference): static
    {
        return new static("Unable to perform an action on the system defined entity with reference '$reference'.");
    }
}
