<?php

namespace App\Domain\ExchangeRate\Services\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ExchangeRateProviderException extends \Exception
{
    public static function unavailable(?\Throwable $previous = null): static
    {
        return new static(
            message: 'Exchange rate provider is currently unavailable.',
            code: Response::HTTP_SERVICE_UNAVAILABLE,
            previous: $previous
        );
    }
}
