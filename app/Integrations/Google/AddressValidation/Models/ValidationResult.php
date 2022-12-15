<?php

namespace App\Integrations\Google\AddressValidation\Models;

final class ValidationResult
{
    public function __construct(
        public readonly ValidationResultVerdict $verdict,
        public readonly ValidationResultAddress $address,
    ) {
    }
}