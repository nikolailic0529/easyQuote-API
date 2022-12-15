<?php

namespace App\Integrations\Google\AddressValidation\Models;

final class ValidateAddressRequest
{
    public function __construct(
        public readonly ValidateAddressRequestAddress $address,
        public readonly ?bool $enableUspsCass = null,
    ) {
    }
}