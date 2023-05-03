<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation\Exceptions;

use Illuminate\Http\Client\Response;

class AddressValidationException extends \Exception
{
    public readonly Response $response;

    protected function __construct(
        Response $response,
        string $message = '',
        int $code = 0
    ) {
        $this->response = $response;

        parent::__construct($message, $code);
    }

    public static function fromResponse(Response $response): static
    {
        return new static(
            response: $response,
            message: sprintf('%s [%s]', $response->json('error.message'), $response->json('error.status')),
            code: $response->json('error.code'),
        );
    }
}
