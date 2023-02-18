<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation\Models;

final class ValidationResult
{
    public function __construct(
        public readonly ValidationResultVerdict $verdict,
        public readonly ValidationResultAddress $address,
    ) {
    }
}
