<?php

namespace App\Domain\DocumentEngine\Exceptions;

use JetBrains\PhpStorm\Pure;

class ClientAuthException extends \Exception implements DocumentEngineClientException
{
    #[Pure]
    public static function requestFailed(int $statusCode): static
    {
        return new static("HTTP request returned status code {$statusCode}");
    }

    #[Pure]
    public static function missingResponsePayload(): static
    {
        return new static("HTTP request didn't return the expected payload.");
    }
}
