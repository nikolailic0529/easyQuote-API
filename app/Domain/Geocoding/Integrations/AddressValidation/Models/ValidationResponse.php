<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation\Models;

final class ValidationResponse
{
    public function __construct(
        public readonly ValidationResult $result,
        public readonly string $responseId,
    ) {
    }
}
