<?php

namespace App\Integrations\Google\AddressValidation\Models;

final class ValidationResultAddress
{
    public function __construct(
        public readonly ?string $formattedAddress,
        public readonly ValidationResultAddressPostalAddress $postalAddress,
    ) {
    }
}