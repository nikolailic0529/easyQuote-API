<?php

namespace App\Integrations\Google\AddressValidation\Models;

final class ValidationResponse
{
    public function __construct(
        public readonly ValidationResult $result,
        public readonly string $responseId,
    ) {
    }
}