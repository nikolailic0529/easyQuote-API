<?php

namespace App\Integrations\Google\AddressValidation;

use App\Integrations\Google\AddressValidation\Models\ValidateAddressRequest;
use App\Integrations\Google\AddressValidation\Models\ValidationResponse;

interface ValidatesAddress
{
    public function validateAddress(ValidateAddressRequest $request): ValidationResponse;
}