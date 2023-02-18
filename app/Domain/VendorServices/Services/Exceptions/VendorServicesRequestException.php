<?php

namespace App\Domain\VendorServices\Services\Exceptions;

use Illuminate\Http\Client\Response;

class VendorServicesRequestException extends \Exception
{
    public function __construct(Response $response, \Throwable $previous = null)
    {
        parent::__construct(message: $this->prepareMessage($response), code: $response->status(), previous: $previous);
    }

    protected function prepareMessage(Response $response): string
    {
        $errorCode = $response->json('ErrorCode');
        $errorDetails = $response->json('ErrorDetails');

        if (is_null($errorCode)) {
            return "Vendor-Services API request returned status code {$response->status()}";
        }

        return "Vendor-Services API request returned error code $errorCode ($errorDetails)";
    }
}
