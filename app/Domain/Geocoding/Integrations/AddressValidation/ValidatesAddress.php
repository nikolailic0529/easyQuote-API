<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation;

use App\Domain\Geocoding\Integrations\AddressValidation\Models\ValidateAddressRequest;
use App\Domain\Geocoding\Integrations\AddressValidation\Models\ValidationResponse;

interface ValidatesAddress
{
    public function validateAddress(ValidateAddressRequest $request): ValidationResponse;
}
